<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Find customer by pppoe_username, username, phone, or email
        $customer = Customer::where('pppoe_username', $request->username)
            ->orWhere('username', $request->username)
            ->orWhere('phone', $request->username)
            ->orWhere('email', $request->username)
            ->first();

        if ($customer && $request->password === $customer->pppoe_password) {
            session(['customer_id' => $customer->id]);
            return redirect()->route('customer.dashboard');
        }

        return back()->with('error', 'Username atau password salah');
    }

    public function logout()
    {
        session()->forget('customer_id');
        return redirect()->route('customer.login');
    }

    protected function getCustomer()
    {
        $customerId = session('customer_id');
        if (!$customerId) {
            return null;
        }
        return Customer::with('package')->find($customerId);
    }

    public function dashboard()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return redirect()->route('customer.login');
        }
        
        $nextInvoice = Invoice::where('customer_id', $customer->id)
            ->where('status', 'unpaid')
            ->orderBy('due_date')
            ->first();

        $recentInvoices = Invoice::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('customer.dashboard', compact('customer', 'nextInvoice', 'recentInvoices'));
    }

    public function invoices()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return redirect()->route('customer.login');
        }
        
        $invoices = Invoice::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('customer.invoices', compact('customer', 'invoices'));
    }

    public function showInvoice(Invoice $invoice)
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return redirect()->route('customer.login');
        }
        
        if ($invoice->customer_id != $customer->id) {
            abort(403);
        }

        return view('customer.invoice-show', compact('customer', 'invoice'));
    }

    /**
     * Create Duitku payment for customer
     */
    public function createDuitkuPayment(Request $request, Invoice $invoice)
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }
        
        if ($invoice->customer_id != $customer->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        if ($invoice->status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Invoice sudah dibayar']);
        }

        $request->validate([
            'payment_method' => 'required|string',
        ]);

        $duitku = app(\App\Services\DuitkuService::class);
        
        if (!$duitku->isEnabled()) {
            return response()->json(['success' => false, 'message' => 'Pembayaran online tidak tersedia']);
        }

        $orderId = 'INV-' . $invoice->id . '-' . time();

        $result = $duitku->createTransaction([
            'order_id' => $orderId,
            'amount' => (int) $invoice->total_amount,
            'payment_method' => $request->payment_method,
            'product_details' => 'Pembayaran ' . $invoice->invoice_number,
            'customer_name' => $customer->name ?? 'Customer',
            'customer_email' => $customer->email ?? '',
            'customer_phone' => $customer->phone ?? '',
            'return_url' => route('customer.invoices.show', $invoice->id),
        ]);

        if ($result['success']) {
            $invoice->update([
                'payment_reference' => $result['reference'],
                'payment_method' => 'duitku_' . $request->payment_method,
                'payment_url' => $result['payment_url'],
            ]);

            return response()->json([
                'success' => true,
                'payment_url' => $result['payment_url'],
                'reference' => $result['reference'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Gagal membuat pembayaran',
        ]);
    }

    public function payments()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return redirect()->route('customer.login');
        }
        
        $payments = Invoice::where('customer_id', $customer->id)
            ->where('status', 'paid')
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return view('customer.payments', compact('customer', 'payments'));
    }

    public function pay(Request $request, Invoice $invoice)
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return redirect()->route('customer.login');
        }
        
        if ($invoice->customer_id != $customer->id) {
            abort(403);
        }

        $paymentService = new PaymentGatewayService();
        $gateway = $request->get('gateway', 'midtrans');

        if ($gateway === 'midtrans') {
            $result = $paymentService->createMidtransPayment($invoice);
        } else {
            $result = $paymentService->createXenditInvoice($invoice);
        }

        if (isset($result['redirect_url'])) {
            return redirect($result['redirect_url']);
        }

        if (isset($result['snap_token'])) {
            return view('customer.pay', compact('invoice', 'result'));
        }

        return back()->with('error', 'Gagal membuat pembayaran');
    }

    public function profile()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return redirect()->route('customer.login');
        }
        
        return view('customer.profile', compact('customer'));
    }

    public function updateProfile(Request $request)
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return redirect()->route('customer.login');
        }
        
        $request->validate([
            'phone' => 'required',
            'email' => 'nullable|email',
        ]);

        $customer->update([
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            $customer->update([
                'pppoe_password' => $request->password,
            ]);
        }

        return back()->with('success', 'Profil berhasil diperbarui');
    }

    public function support()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return redirect()->route('customer.login');
        }
        
        return view('customer.support', compact('customer'));
    }

    public function submitTicket(Request $request)
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return redirect()->route('customer.login');
        }

        $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'required|in:billing,technical,installation,complaint,inquiry,other',
            'priority' => 'in:low,medium,high',
            'message' => 'required|string',
        ]);

        \App\Models\Ticket::create([
            'ticket_number' => 'TKT-' . date('Ymd') . '-' . str_pad(\App\Models\Ticket::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'subject' => $request->subject,
            'description' => $request->message,
            'category' => $request->category,
            'priority' => $request->priority ?? 'medium',
            'status' => 'open',
        ]);
        
        return back()->with('success', 'Tiket berhasil dikirim. Tim kami akan segera menghubungi Anda.');
    }

    public function tickets()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return redirect()->route('customer.login');
        }
        
        $tickets = \App\Models\Ticket::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('customer.tickets', compact('customer', 'tickets'));
    }

    public function usage()
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return redirect()->route('customer.login');
        }
        
        return view('customer.usage', compact('customer'));
    }
}
