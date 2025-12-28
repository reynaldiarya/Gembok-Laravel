<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DuitkuService;
use App\Services\WhatsAppService;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DuitkuCallbackController extends Controller
{
    /**
     * Handle Duitku callback
     */
    public function callback(Request $request)
    {
        Log::info('Duitku callback received', $request->all());

        $merchantCode = $request->input('merchantCode');
        $amount = $request->input('amount');
        $merchantOrderId = $request->input('merchantOrderId');
        $productDetail = $request->input('productDetail');
        $additionalParam = $request->input('additionalParam');
        $paymentCode = $request->input('paymentCode');
        $resultCode = $request->input('resultCode');
        $merchantUserId = $request->input('merchantUserId');
        $reference = $request->input('reference');
        $signature = $request->input('signature');
        $publisherOrderId = $request->input('publisherOrderId');
        $spUserHash = $request->input('spUserHash');
        $settlementDate = $request->input('settlementDate');
        $issuerCode = $request->input('issuerCode');

        // Verify signature
        $duitku = app(DuitkuService::class);
        if (!$duitku->verifyCallback($merchantCode, $amount, $merchantOrderId, $signature)) {
            Log::warning('Duitku callback invalid signature', [
                'merchantOrderId' => $merchantOrderId,
                'signature' => $signature,
            ]);
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        // Parse invoice ID from merchantOrderId (format: INV-{id}-{timestamp})
        $parts = explode('-', $merchantOrderId);
        $invoiceId = $parts[1] ?? null;

        if (!$invoiceId) {
            Log::error('Duitku callback: Cannot parse invoice ID', ['merchantOrderId' => $merchantOrderId]);
            return response()->json(['status' => 'error', 'message' => 'Invalid order ID'], 400);
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            Log::error('Duitku callback: Invoice not found', ['invoiceId' => $invoiceId]);
            return response()->json(['status' => 'error', 'message' => 'Invoice not found'], 404);
        }

        // Check result code
        if ($resultCode === '00') {
            // Payment successful
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_reference' => $reference,
                'payment_method' => 'duitku_' . $paymentCode,
            ]);

            Log::info('Duitku payment successful', [
                'invoiceId' => $invoiceId,
                'reference' => $reference,
                'amount' => $amount,
            ]);

            // Send WhatsApp notification
            $this->sendPaymentNotification($invoice);

            return response()->json(['status' => 'success']);
        } else {
            // Payment failed or pending
            Log::info('Duitku payment not successful', [
                'invoiceId' => $invoiceId,
                'resultCode' => $resultCode,
            ]);

            return response()->json(['status' => 'received']);
        }
    }

    /**
     * Send payment success notification via WhatsApp
     */
    protected function sendPaymentNotification(Invoice $invoice)
    {
        try {
            $customer = $invoice->customer;
            if (!$customer || !$customer->phone) {
                return;
            }

            $whatsapp = app(WhatsAppService::class);
            
            $message = "✅ *Pembayaran Berhasil*\n\n";
            $message .= "Halo {$customer->name},\n\n";
            $message .= "Pembayaran untuk invoice *{$invoice->invoice_number}* telah berhasil.\n\n";
            $message .= "Detail:\n";
            $message .= "• Total: Rp " . number_format($invoice->total_amount, 0, ',', '.') . "\n";
            $message .= "• Tanggal: " . now()->format('d/m/Y H:i') . "\n";
            $message .= "• Ref: {$invoice->payment_reference}\n\n";
            $message .= "Terima kasih telah melakukan pembayaran.\n\n";
            $message .= companyName();

            $whatsapp->sendMessage($customer->phone, $message);
        } catch (\Exception $e) {
            Log::error('Failed to send payment notification: ' . $e->getMessage());
        }
    }
}
