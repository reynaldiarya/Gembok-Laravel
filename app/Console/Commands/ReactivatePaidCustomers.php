<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\MikrotikService;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReactivatePaidCustomers extends Command
{
    protected $signature = 'billing:reactivate-paid {--dry-run : Preview without making changes}';
    protected $description = 'Reactivate suspended customers who have paid their invoices';

    protected $mikrotik;
    protected $whatsapp;

    public function __construct(MikrotikService $mikrotik, WhatsAppService $whatsapp)
    {
        parent::__construct();
        $this->mikrotik = $mikrotik;
        $this->whatsapp = $whatsapp;
    }

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info("Finding suspended customers with paid invoices...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Find suspended customers
        $suspendedCustomers = Customer::where('status', 'suspended')
            ->with(['invoices' => function($q) {
                $q->where('status', 'unpaid')->orderBy('due_date', 'desc');
            }])
            ->get();

        $reactivated = 0;
        $notified = 0;
        $errors = 0;

        foreach ($suspendedCustomers as $customer) {
            // Check if customer has no unpaid invoices
            if ($customer->invoices->isEmpty()) {
                $this->line("Reactivating: {$customer->name} ({$customer->pppoe_username})");

                if (!$dryRun) {
                    try {
                        // Update customer status
                        $customer->update(['status' => 'active']);
                        $reactivated++;

                        // Re-enable on Mikrotik if connected
                        if ($customer->pppoe_username && $this->mikrotik->isConnected()) {
                            // PPPoE secret should already exist, just enable it
                            $this->mikrotik->enablePPPoESecret($customer->pppoe_username);
                        }

                        // Send WhatsApp notification
                        if ($customer->phone) {
                            $message = "Halo {$customer->name},\n\n";
                            $message .= "Terima kasih atas pembayaran Anda. Layanan internet Anda telah diaktifkan kembali.\n\n";
                            $message .= "Selamat menikmati layanan kami!\n\n";
                            $message .= "- Tim " . companyName();

                            $result = $this->whatsapp->sendMessage($customer->phone, $message);
                            if ($result['success'] ?? false) {
                                $notified++;
                            }
                        }

                        Log::info('Customer reactivated', ['customer_id' => $customer->id]);

                    } catch (\Exception $e) {
                        $errors++;
                        Log::error('Failed to reactivate customer', [
                            'customer_id' => $customer->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    $reactivated++;
                }
            }
        }

        $this->newLine();
        $this->info("Reactivation process completed!");
        $this->table(
            ['Action', 'Count'],
            [
                ['Reactivated', $reactivated],
                ['Notified', $notified],
                ['Errors', $errors],
            ]
        );

        return Command::SUCCESS;
    }
}
