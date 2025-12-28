<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DuitkuService;
use App\Models\Invoice;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DuitkuController extends Controller
{
    protected $duitku;

    public function __construct(DuitkuService $duitku)
    {
        $this->duitku = $duitku;
    }

    /**
     * Show Duitku settings page
     */
    public function settings()
    {
        $settings = [
            'merchant_code' => AppSetting::getValue('duitku_merchant_code', ''),
            'api_key' => AppSetting::getValue('duitku_api_key', ''),
            'is_production' => AppSetting::getValue('duitku_is_production', 'false'),
            'enabled' => AppSetting::getValue('duitku_enabled', 'false'),
        ];

        return view('admin.settings.duitku', compact('settings'));
    }

    /**
     * Save Duitku settings
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'merchant_code' => 'required|string',
            'api_key' => 'required|string',
        ]);

        AppSetting::setValue('duitku_merchant_code', $request->merchant_code, 'duitku');
        AppSetting::setValue('duitku_api_key', $request->api_key, 'duitku');
        AppSetting::setValue('duitku_is_production', $request->has('is_production') ? 'true' : 'false', 'duitku');
        AppSetting::setValue('duitku_enabled', $request->has('enabled') ? 'true' : 'false', 'duitku');

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget('app_setting_duitku_merchant_code');
        \Illuminate\Support\Facades\Cache::forget('app_setting_duitku_api_key');
        \Illuminate\Support\Facades\Cache::forget('app_setting_duitku_is_production');
        \Illuminate\Support\Facades\Cache::forget('app_setting_duitku_enabled');

        return redirect()->back()->with('success', 'Pengaturan Duitku berhasil disimpan');
    }

    /**
     * Test Duitku connection
     */
    public function testConnection()
    {
        $result = $this->duitku->getPaymentMethods(10000);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Koneksi Duitku berhasil!',
                'methods_count' => count($result['methods']),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Koneksi gagal: ' . ($result['message'] ?? 'Unknown error'),
        ]);
    }

    /**
     * Get payment methods for an amount
     */
    public function getPaymentMethods(Request $request)
    {
        $amount = $request->input('amount', 10000);
        $result = $this->duitku->getPaymentMethods($amount);

        return response()->json($result);
    }

    /**
     * Create payment for invoice
     */
    public function createPayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'payment_method' => 'required|string',
        ]);

        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Invoice sudah dibayar',
            ]);
        }

        $customer = $invoice->customer;
        $orderId = 'INV-' . $invoice->id . '-' . time();

        $result = $this->duitku->createTransaction([
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
            // Save payment reference to invoice
            $invoice->update([
                'payment_reference' => $result['reference'],
                'payment_method' => 'duitku_' . $request->payment_method,
                'payment_url' => $result['payment_url'],
            ]);

            return response()->json([
                'success' => true,
                'payment_url' => $result['payment_url'],
                'reference' => $result['reference'],
                'va_number' => $result['va_number'],
                'qr_string' => $result['qr_string'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Gagal membuat pembayaran',
        ]);
    }

    /**
     * Check payment status
     */
    public function checkStatus(Request $request)
    {
        $invoiceId = $request->input('invoice_id');
        $invoice = Invoice::find($invoiceId);

        if (!$invoice || !$invoice->payment_reference) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice tidak ditemukan atau belum ada pembayaran',
            ]);
        }

        // Extract order ID from reference or use stored one
        $orderId = 'INV-' . $invoice->id;
        
        $result = $this->duitku->checkTransaction($orderId);

        if ($result['success']) {
            // Update invoice if paid
            if ($result['status_code'] === '00') {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'status_code' => $result['status_code'],
                'status_message' => $result['status_message'],
                'is_paid' => $result['status_code'] === '00',
            ]);
        }

        return response()->json($result);
    }

    /**
     * Send payment link via WhatsApp
     */
    public function sendPaymentLink(Request $request, Invoice $invoice)
    {
        if (!$invoice->payment_url) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada link pembayaran. Buat pembayaran terlebih dahulu.',
            ]);
        }

        $customer = $invoice->customer;
        if (!$customer->phone) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak memiliki nomor telepon',
            ]);
        }

        // Send via WhatsApp
        $whatsapp = app(\App\Services\WhatsAppService::class);
        $message = "Halo {$customer->name},\n\n";
        $message .= "Berikut link pembayaran untuk invoice {$invoice->invoice_number}:\n";
        $message .= "Total: Rp " . number_format($invoice->total_amount, 0, ',', '.') . "\n\n";
        $message .= "Link Pembayaran:\n{$invoice->payment_url}\n\n";
        $message .= "Terima kasih.\n" . companyName();

        $result = $whatsapp->sendMessage($customer->phone, $message);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Link pembayaran berhasil dikirim via WhatsApp',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengirim WhatsApp: ' . ($result['message'] ?? 'Unknown error'),
        ]);
    }
}
