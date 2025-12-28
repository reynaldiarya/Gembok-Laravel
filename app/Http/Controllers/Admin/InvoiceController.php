<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Events\InvoicePaid;
use App\Services\WhatsAppService;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    protected $whatsapp;
    protected $paymentGateway;

    public function __construct(WhatsAppService $whatsapp, PaymentGatewayService $paymentGateway)
    {
        $this->whatsapp = $whatsapp;
        $this->paymentGateway = $paymentGateway;
    }

    public function index(Request $request)
    {
        $query = \App\Models\Invoice::with(['customer', 'package']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $invoices = $query->latest()->paginate(20);
        $customers = \App\Models\Customer::orderBy('name')->get();

        $stats = [
            'total' => \App\Models\Invoice::count(),
            'paid' => \App\Models\Invoice::where('status', 'paid')->count(),
            'unpaid' => \App\Models\Invoice::where('status', 'unpaid')->count(),
            'total_revenue' => \App\Models\Invoice::where('status', 'paid')->sum('amount'),
            'pending_revenue' => \App\Models\Invoice::where('status', 'unpaid')->sum('amount'),
        ];

        return view('admin.invoices.index', compact('invoices', 'customers', 'stats'));
    }

    public function create()
    {
        $customers = \App\Models\Customer::where('status', 'active')->orderBy('name')->get();
        $packages = \App\Models\Package::where('is_active', true)->get();
        return view('admin.invoices.create', compact('customers', 'packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'package_id' => 'nullable|exists:packages,id',
            'amount' => 'required|integer|min:0',
            'tax_amount' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'invoice_type' => 'required|in:monthly,installation,voucher,other',
        ]);

        // Generate invoice number
        $lastInvoice = \App\Models\Invoice::latest()->first();
        $number = $lastInvoice ? (int)substr($lastInvoice->invoice_number, 4) + 1 : 1;
        $validated['invoice_number'] = 'INV-' . str_pad($number, 6, '0', STR_PAD_LEFT);
        $validated['status'] = 'unpaid';
        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;

        \App\Models\Invoice::create($validated);

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Invoice created successfully!');
    }

    public function show(\App\Models\Invoice $invoice)
    {
        $invoice->load(['customer', 'package']);
        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit(\App\Models\Invoice $invoice)
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        $packages = \App\Models\Package::where('is_active', true)->get();
        return view('admin.invoices.edit', compact('invoice', 'customers', 'packages'));
    }

    public function update(Request $request, \App\Models\Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'package_id' => 'nullable|exists:packages,id',
            'amount' => 'required|integer|min:0',
            'tax_amount' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'invoice_type' => 'required|in:monthly,installation,voucher,other',
        ]);

        $invoice->update($validated);

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Invoice updated successfully!');
    }

    public function destroy(\App\Models\Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->route('admin.invoices.index')
                ->with('error', 'Cannot delete paid invoice!');
        }

        $invoice->delete();

        return redirect()->route('admin.invoices.index')
            ->with('success', 'Invoice deleted successfully!');
    }

    public function pay(Request $request, \App\Models\Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return redirect()->back()
                ->with('error', 'Invoice already paid!');
        }

        $invoice->update([
            'status' => 'paid',
            'paid_date' => now(),
            'payment_method' => $request->input('payment_method', 'cash'),
            'collected_by' => auth()->id(),
        ]);

        // Fire event for automatic activation
        event(new InvoicePaid($invoice));

        return redirect()->back()
            ->with('success', 'Invoice marked as paid!');
    }

    /**
     * Send invoice notification via WhatsApp
     */
    public function sendNotification(\App\Models\Invoice $invoice)
    {
        $customer = $invoice->customer;

        if (!$customer || !$customer->phone) {
            return response()->json([
                'success' => false,
                'message' => 'Customer phone not found'
            ], 404);
        }

        $result = $this->whatsapp->sendInvoiceNotification($customer, $invoice);

        return response()->json($result);
    }

    /**
     * Create payment link
     */
    public function createPaymentLink(\App\Models\Invoice $invoice)
    {
        $customer = $invoice->customer;

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Customer not found'
            ], 404);
        }

        $result = $this->paymentGateway->createPayment($invoice, $customer);

        if ($result['success']) {
            $invoice->update([
                'payment_gateway' => $result['gateway'],
                'payment_order_id' => $result['order_id'],
            ]);
        }

        return response()->json($result);
    }

    /**
     * Send payment link via WhatsApp
     */
    public function sendPaymentLink(\App\Models\Invoice $invoice)
    {
        $customer = $invoice->customer;

        if (!$customer || !$customer->phone) {
            return response()->json([
                'success' => false,
                'message' => 'Customer phone not found'
            ], 404);
        }

        // Create payment link first
        $paymentResult = $this->paymentGateway->createPayment($invoice, $customer);

        if (!$paymentResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment link'
            ], 500);
        }

        // Send via WhatsApp
        $message = "Halo *{$customer->name}*,\n\n";
        $message .= "Berikut link pembayaran untuk tagihan Anda:\n\n";
        $message .= "📋 *Invoice:* {$invoice->invoice_number}\n";
        $message .= "💰 *Total:* Rp " . number_format($invoice->amount, 0, ',', '.') . "\n\n";
        $message .= "🔗 *Link Pembayaran:*\n{$paymentResult['payment_url']}\n\n";
        $message .= "Link ini berlaku selama 24 jam.\n\n";
        $message .= "Terima kasih,\n";
        $message .= "*" . companyName() . "*";

        $waResult = $this->whatsapp->send($customer->phone, $message);

        return response()->json([
            'success' => $waResult['success'],
            'message' => $waResult['success'] ? 'Payment link sent via WhatsApp' : 'Failed to send WhatsApp',
            'payment_url' => $paymentResult['payment_url']
        ]);
    }

    public function print(\App\Models\Invoice $invoice)
    {
        $invoice->load(['customer', 'package']);
        $company = [
            'name' => \App\Models\AppSetting::where('key', 'company_name')->value('value') ?? 'GEMBOK LARA',
            'phone' => \App\Models\AppSetting::where('key', 'company_phone')->value('value') ?? '-',
            'email' => \App\Models\AppSetting::where('key', 'company_email')->value('value') ?? '-',
            'address' => \App\Models\AppSetting::where('key', 'company_address')->value('value') ?? '-',
        ];
        
        return view('admin.invoices.print', compact('invoice', 'company'));
    }
}
