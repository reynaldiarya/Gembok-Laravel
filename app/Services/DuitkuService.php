<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AppSetting;

class DuitkuService
{
    protected $merchantCode;
    protected $apiKey;
    protected $baseUrl;
    protected $isProduction;
    protected $callbackUrl;

    public function __construct()
    {
        $this->merchantCode = AppSetting::getValue('duitku_merchant_code', '');
        $this->apiKey = AppSetting::getValue('duitku_api_key', '');
        $this->isProduction = AppSetting::getValue('duitku_is_production', 'false') === 'true';
        
        $this->baseUrl = $this->isProduction 
            ? 'https://passport.duitku.com/webapi/api/merchant'
            : 'https://sandbox.duitku.com/webapi/api/merchant';
            
        $this->callbackUrl = config('app.url') . '/api/duitku/callback';
    }

    /**
     * Check if Duitku is enabled and configured
     */
    public function isEnabled()
    {
        return AppSetting::getValue('duitku_enabled', 'false') === 'true'
            && !empty($this->merchantCode)
            && !empty($this->apiKey);
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods($amount)
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Duitku tidak aktif'];
        }

        $datetime = date('Y-m-d H:i:s');
        $signature = hash('sha256', $this->merchantCode . $amount . $datetime . $this->apiKey);

        try {
            $response = Http::post($this->baseUrl . '/paymentmethod/getpaymentmethod', [
                'merchantcode' => $this->merchantCode,
                'amount' => $amount,
                'datetime' => $datetime,
                'signature' => $signature,
            ]);

            $data = $response->json();
            
            if (isset($data['paymentFee'])) {
                return [
                    'success' => true,
                    'methods' => $data['paymentFee'],
                ];
            }

            return [
                'success' => false,
                'message' => $data['Message'] ?? 'Gagal mendapatkan metode pembayaran',
            ];
        } catch (\Exception $e) {
            Log::error('Duitku getPaymentMethods error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create payment transaction
     */
    public function createTransaction($params)
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Duitku tidak aktif'];
        }

        $merchantOrderId = $params['order_id'];
        $amount = $params['amount'];
        $paymentMethod = $params['payment_method'];
        $productDetails = $params['product_details'] ?? 'Pembayaran Invoice';
        $customerName = $params['customer_name'] ?? '';
        $customerEmail = $params['customer_email'] ?? '';
        $customerPhone = $params['customer_phone'] ?? '';
        $returnUrl = $params['return_url'] ?? config('app.url');
        $expiryPeriod = $params['expiry_period'] ?? 1440; // 24 hours default

        $signature = md5($this->merchantCode . $merchantOrderId . $amount . $this->apiKey);

        $payload = [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $amount,
            'paymentMethod' => $paymentMethod,
            'merchantOrderId' => $merchantOrderId,
            'productDetails' => $productDetails,
            'customerVaName' => $customerName,
            'email' => $customerEmail,
            'phoneNumber' => $customerPhone,
            'callbackUrl' => $this->callbackUrl,
            'returnUrl' => $returnUrl,
            'signature' => $signature,
            'expiryPeriod' => $expiryPeriod,
        ];

        try {
            $response = Http::post($this->baseUrl . '/v2/inquiry', $payload);
            $data = $response->json();

            Log::info('Duitku createTransaction response', ['response' => $data]);

            if (isset($data['statusCode']) && $data['statusCode'] == '00') {
                return [
                    'success' => true,
                    'reference' => $data['reference'],
                    'payment_url' => $data['paymentUrl'],
                    'va_number' => $data['vaNumber'] ?? null,
                    'qr_string' => $data['qrString'] ?? null,
                    'amount' => $data['amount'] ?? $amount,
                    'status_code' => $data['statusCode'],
                    'status_message' => $data['statusMessage'] ?? 'Success',
                ];
            }

            return [
                'success' => false,
                'message' => $data['statusMessage'] ?? 'Gagal membuat transaksi',
                'status_code' => $data['statusCode'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Duitku createTransaction error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check transaction status
     */
    public function checkTransaction($merchantOrderId)
    {
        if (!$this->isEnabled()) {
            return ['success' => false, 'message' => 'Duitku tidak aktif'];
        }

        $signature = md5($this->merchantCode . $merchantOrderId . $this->apiKey);

        try {
            $response = Http::post($this->baseUrl . '/transactionStatus', [
                'merchantCode' => $this->merchantCode,
                'merchantOrderId' => $merchantOrderId,
                'signature' => $signature,
            ]);

            $data = $response->json();

            if (isset($data['statusCode'])) {
                return [
                    'success' => true,
                    'status_code' => $data['statusCode'],
                    'status_message' => $data['statusMessage'] ?? '',
                    'reference' => $data['reference'] ?? null,
                    'amount' => $data['amount'] ?? null,
                ];
            }

            return [
                'success' => false,
                'message' => $data['Message'] ?? 'Gagal cek status transaksi',
            ];
        } catch (\Exception $e) {
            Log::error('Duitku checkTransaction error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Verify callback signature
     */
    public function verifyCallback($merchantCode, $amount, $merchantOrderId, $signature)
    {
        $expectedSignature = md5($merchantCode . $amount . $merchantOrderId . $this->apiKey);
        return $signature === $expectedSignature;
    }

    /**
     * Get payment method name
     */
    public static function getPaymentMethodName($code)
    {
        $methods = [
            'VC' => 'Credit Card (Visa/Master)',
            'BC' => 'BCA Virtual Account',
            'M2' => 'Mandiri Virtual Account',
            'VA' => 'Maybank Virtual Account',
            'I1' => 'BNI Virtual Account',
            'B1' => 'CIMB Niaga Virtual Account',
            'BT' => 'Permata Bank Virtual Account',
            'A1' => 'ATM Bersama',
            'AG' => 'Bank Artha Graha',
            'NC' => 'Bank Neo Commerce/BNC',
            'BR' => 'BRIVA',
            'S1' => 'Bank Sahabat Sampoerna',
            'DM' => 'Danamon Virtual Account',
            'OV' => 'OVO',
            'SA' => 'ShopeePay Apps',
            'LF' => 'LinkAja Apps (Fixed)',
            'LA' => 'LinkAja Apps (Percentage)',
            'DA' => 'DANA',
            'IR' => 'Indomaret',
            'A2' => 'POS Indonesia',
            'FT' => 'Pegadaian',
            'SP' => 'ShopeePay',
            'QR' => 'QRIS',
        ];

        return $methods[$code] ?? $code;
    }

    /**
     * Get popular payment methods for display
     */
    public static function getPopularMethods()
    {
        return [
            ['code' => 'QR', 'name' => 'QRIS', 'icon' => 'qrcode', 'category' => 'E-Wallet'],
            ['code' => 'BC', 'name' => 'BCA Virtual Account', 'icon' => 'university', 'category' => 'Virtual Account'],
            ['code' => 'M2', 'name' => 'Mandiri Virtual Account', 'icon' => 'university', 'category' => 'Virtual Account'],
            ['code' => 'I1', 'name' => 'BNI Virtual Account', 'icon' => 'university', 'category' => 'Virtual Account'],
            ['code' => 'BR', 'name' => 'BRI Virtual Account', 'icon' => 'university', 'category' => 'Virtual Account'],
            ['code' => 'OV', 'name' => 'OVO', 'icon' => 'wallet', 'category' => 'E-Wallet'],
            ['code' => 'DA', 'name' => 'DANA', 'icon' => 'wallet', 'category' => 'E-Wallet'],
            ['code' => 'SP', 'name' => 'ShopeePay', 'icon' => 'wallet', 'category' => 'E-Wallet'],
            ['code' => 'IR', 'name' => 'Indomaret', 'icon' => 'store', 'category' => 'Retail'],
        ];
    }
}
