<?php

namespace App\Services;

use App\Models\WhatsappLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $apiUrl;
    protected $apiKey;
    protected $sender;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiKey = config('services.whatsapp.api_key');
        $this->sender = config('services.whatsapp.sender');
    }

    /**
     * Send WhatsApp message via Fonnte API
     */
    public function send($phone, $message)
    {
        try {
            $phone = $this->formatPhone($phone);

            // Fonnte API format
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->asForm()->post($this->apiUrl . '/send', [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            $result = $response->json();

            if ($response->successful() && isset($result['status']) && $result['status'] === true) {
                Log::info('WhatsApp message sent via Fonnte', ['phone' => $phone]);
                return [
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => $result
                ];
            }

            Log::error('WhatsApp send failed', [
                'phone' => $phone,
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => $result['reason'] ?? 'Failed to send message',
                'error' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Send invoice notification
     */
    public function sendInvoiceNotification($customer, $invoice)
    {
        $message = "Halo *{$customer->name}*,\n\n";
        $message .= "Tagihan internet Anda telah terbit:\n\n";
        $message .= "📋 *Invoice:* {$invoice->invoice_number}\n";
        $message .= "📦 *Paket:* {$invoice->package->name}\n";
        $message .= "💰 *Total:* Rp " . number_format($invoice->amount, 0, ',', '.') . "\n";
        $message .= "📅 *Jatuh Tempo:* " . ($invoice->due_date ? $invoice->due_date->format('d M Y') : '-') . "\n\n";
        $message .= "Silakan lakukan pembayaran sebelum jatuh tempo.\n\n";
        $message .= "Terima kasih,\n";
        $message .= "*" . companyName() . "*";

        $result = $this->send($customer->phone, $message);
        $this->logMessage($customer->phone, 'invoice', $message, $result, $customer->id, $invoice->id);
        return $result;
    }

    /**
     * Send payment confirmation
     */
    public function sendPaymentConfirmation($customer, $invoice)
    {
        $message = "Halo *{$customer->name}*,\n\n";
        $message .= "✅ Pembayaran Anda telah kami terima!\n\n";
        $message .= "📋 *Invoice:* {$invoice->invoice_number}\n";
        $message .= "💰 *Jumlah:* Rp " . number_format($invoice->amount, 0, ',', '.') . "\n";
        $message .= "📅 *Tanggal Bayar:* " . ($invoice->paid_date ? $invoice->paid_date->format('d M Y') : now()->format('d M Y')) . "\n\n";
        $message .= "Terima kasih atas pembayaran Anda.\n\n";
        $message .= "*" . companyName() . "*";

        return $this->send($customer->phone, $message);
    }

    /**
     * Send voucher to customer
     */
    public function sendVoucher($phone, $vouchers, $package)
    {
        $message = "🎫 *Voucher Internet Anda*\n\n";
        $message .= "Paket: *{$package}*\n\n";
        
        foreach ($vouchers as $index => $voucher) {
            $message .= "Voucher " . ($index + 1) . ":\n";
            $message .= "👤 Username: `{$voucher['code']}`\n";
            $message .= "🔑 Password: `{$voucher['password']}`\n\n";
        }
        
        $message .= "Cara pakai:\n";
        $message .= "1. Hubungkan ke WiFi\n";
        $message .= "2. Buka browser\n";
        $message .= "3. Masukkan username & password\n\n";
        $message .= "Terima kasih!\n";
        $message .= "*" . companyName() . "*";

        return $this->send($phone, $message);
    }

    /**
     * Send payment reminder
     */
    public function sendPaymentReminder($customer, $invoice)
    {
        $message = "⚠️ *Pengingat Pembayaran*\n\n";
        $message .= "Halo *{$customer->name}*,\n\n";
        $message .= "Tagihan Anda belum dibayar:\n\n";
        $message .= "📋 *Invoice:* {$invoice->invoice_number}\n";
        $message .= "💰 *Total:* Rp " . number_format($invoice->amount, 0, ',', '.') . "\n";
        $message .= "📅 *Jatuh Tempo:* " . ($invoice->due_date ? $invoice->due_date->format('d M Y') : '-') . "\n\n";
        $message .= "Mohon segera lakukan pembayaran untuk menghindari pemutusan layanan.\n\n";
        $message .= "*" . companyName() . "*";

        $result = $this->send($customer->phone, $message);
        $this->logMessage($customer->phone, 'reminder', $message, $result, $customer->id, $invoice->id);
        return $result;
    }

    /**
     * Send suspension notice
     */
    public function sendSuspensionNotice($customer)
    {
        $message = "🚫 *Pemberitahuan Penangguhan Layanan*\n\n";
        $message .= "Halo *{$customer->name}*,\n\n";
        $message .= "Layanan internet Anda telah ditangguhkan karena tunggakan pembayaran.\n\n";
        $message .= "Silakan hubungi kami atau lakukan pembayaran untuk mengaktifkan kembali layanan Anda.\n\n";
        $message .= "*" . companyName() . "*";

        $result = $this->send($customer->phone, $message);
        $this->logMessage($customer->phone, 'suspension', $message, $result, $customer->id);
        return $result;
    }

    /**
     * Log WhatsApp message
     */
    protected function logMessage($phone, $type, $message, $result, $customerId = null, $invoiceId = null)
    {
        try {
            WhatsappLog::create([
                'phone' => $phone,
                'type' => $type,
                'customer_id' => $customerId,
                'invoice_id' => $invoiceId,
                'message' => $message,
                'status' => $result['success'] ? 'sent' : 'failed',
                'response' => $result['data'] ?? null,
                'error_message' => $result['success'] ? null : ($result['message'] ?? 'Unknown error'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log WhatsApp message: ' . $e->getMessage());
        }
    }

    /**
     * Format phone number to international format
     */
    protected function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }
        
        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }
        
        return $phone;
    }

    /**
     * Check connection status via Fonnte API
     */
    public function checkStatus()
    {
        try {
            // Fonnte device status endpoint
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->post($this->apiUrl . '/device');

            $result = $response->json();

            if ($response->successful() && isset($result['status']) && $result['status'] === true) {
                return [
                    'connected' => true,
                    'device' => $result['device'] ?? null,
                    'status' => $result
                ];
            }

            return [
                'connected' => false,
                'message' => $result['reason'] ?? 'Device not connected'
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp status check failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if service is connected
     */
    public function isConnected()
    {
        $status = $this->checkStatus();
        return $status && isset($status['connected']) && $status['connected'] === true;
    }
}
