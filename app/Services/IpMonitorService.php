<?php

namespace App\Services;

use App\Models\IpMonitor;
use App\Models\IpMonitorLog;
use App\Models\NetworkAlert;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

class IpMonitorService
{
    /**
     * Ping an IP address and return result
     */
    public function ping(string $ip, int $count = 3, int $timeout = 2): array
    {
        $result = [
            'ip' => $ip,
            'status' => 'down',
            'latency_ms' => null,
            'packet_loss' => 100,
            'packets_sent' => $count,
            'packets_received' => 0,
        ];

        // Use system ping command
        $cmd = PHP_OS_FAMILY === 'Windows' 
            ? "ping -n {$count} -w " . ($timeout * 1000) . " {$ip}"
            : "ping -c {$count} -W {$timeout} {$ip} 2>&1";

        exec($cmd, $output, $returnCode);
        $outputStr = implode("\n", $output);

        // Parse results
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows parsing
            if (preg_match('/Received = (\d+)/', $outputStr, $matches)) {
                $result['packets_received'] = (int) $matches[1];
            }
            if (preg_match('/Average = (\d+)ms/', $outputStr, $matches)) {
                $result['latency_ms'] = (int) $matches[1];
            }
        } else {
            // Linux/Mac parsing
            if (preg_match('/(\d+) received/', $outputStr, $matches)) {
                $result['packets_received'] = (int) $matches[1];
            }
            if (preg_match('/min\/avg\/max.*= [\d.]+\/([\d.]+)\//', $outputStr, $matches)) {
                $result['latency_ms'] = (int) round((float) $matches[1]);
            }
            // Alternative pattern for some systems
            if (!$result['latency_ms'] && preg_match('/time[=<](\d+)/', $outputStr, $matches)) {
                $result['latency_ms'] = (int) $matches[1];
            }
        }

        // Calculate packet loss
        if ($result['packets_sent'] > 0) {
            $result['packet_loss'] = round((1 - ($result['packets_received'] / $result['packets_sent'])) * 100, 2);
        }

        // Determine status
        if ($result['packets_received'] > 0) {
            $result['status'] = 'up';
        }

        return $result;
    }

    /**
     * Check a single IP monitor
     */
    public function checkMonitor(IpMonitor $monitor): array
    {
        $pingResult = $this->ping($monitor->ip_address);
        
        $previousStatus = $monitor->status;
        $newStatus = $pingResult['status'];

        // Update monitor
        $updateData = [
            'status' => $newStatus,
            'latency_ms' => $pingResult['latency_ms'],
            'packet_loss' => $pingResult['packet_loss'],
            'last_check' => now(),
        ];

        if ($newStatus === 'up') {
            $updateData['last_up'] = now();
            $updateData['consecutive_failures'] = 0;
        } else {
            $updateData['last_down'] = now();
            $updateData['consecutive_failures'] = $monitor->consecutive_failures + 1;
        }

        $monitor->update($updateData);

        // Log the check
        IpMonitorLog::create([
            'ip_monitor_id' => $monitor->id,
            'status' => $newStatus,
            'latency_ms' => $pingResult['latency_ms'],
            'packet_loss' => $pingResult['packet_loss'],
            'checked_at' => now(),
        ]);

        // Handle alerts
        $this->handleAlerts($monitor, $previousStatus, $newStatus);

        return $pingResult;
    }

    /**
     * Check all active monitors
     */
    public function checkAllMonitors(): array
    {
        $monitors = IpMonitor::active()->get();
        $results = [];

        foreach ($monitors as $monitor) {
            // Check if it's time to check this monitor
            if ($monitor->last_check && $monitor->last_check->diffInSeconds(now()) < $monitor->check_interval) {
                continue;
            }

            $results[$monitor->id] = $this->checkMonitor($monitor);
        }

        return $results;
    }

    /**
     * Handle alert creation and notifications
     */
    protected function handleAlerts(IpMonitor $monitor, string $previousStatus, string $newStatus): void
    {
        if (!$monitor->alert_enabled) {
            return;
        }

        // Status changed from up to down
        if ($previousStatus === 'up' && $newStatus === 'down') {
            // Check if threshold reached
            if ($monitor->consecutive_failures >= $monitor->alert_threshold) {
                $this->createAlert($monitor, 'down', 
                    "IP {$monitor->ip_address} is DOWN",
                    "IP {$monitor->ip_address} ({$monitor->display_name}) has been unreachable for {$monitor->consecutive_failures} consecutive checks."
                );
            }
        }

        // Status changed from down to up (recovery)
        if ($previousStatus === 'down' && $newStatus === 'up') {
            $this->createAlert($monitor, 'recovery',
                "IP {$monitor->ip_address} is UP",
                "IP {$monitor->ip_address} ({$monitor->display_name}) is now reachable. Latency: {$monitor->latency_ms}ms"
            );

            // Resolve previous down alerts
            NetworkAlert::where('alertable_type', IpMonitor::class)
                ->where('alertable_id', $monitor->id)
                ->where('type', 'down')
                ->whereNull('resolved_at')
                ->update(['resolved_at' => now()]);
        }
    }

    /**
     * Create an alert
     */
    protected function createAlert(IpMonitor $monitor, string $type, string $title, string $message): void
    {
        $alert = NetworkAlert::create([
            'alertable_type' => IpMonitor::class,
            'alertable_id' => $monitor->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
        ]);

        // Send WhatsApp notification
        $this->sendNotification($monitor, $alert);
    }

    /**
     * Send notification via WhatsApp
     */
    protected function sendNotification(IpMonitor $monitor, NetworkAlert $alert): void
    {
        try {
            $whatsapp = app(WhatsAppService::class);
            
            // Get admin phone from settings
            $adminPhone = appSetting('company_phone');
            if (!$adminPhone) {
                return;
            }

            $emoji = $alert->type === 'down' ? '🔴' : '🟢';
            $message = "{$emoji} *Network Alert*\n\n";
            $message .= "{$alert->title}\n\n";
            $message .= "{$alert->message}\n\n";
            $message .= "Time: " . now()->format('d/m/Y H:i:s');

            $result = $whatsapp->sendMessage($adminPhone, $message);
            
            if ($result['success']) {
                $alert->update(['notification_sent' => true]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send IP monitor notification: ' . $e->getMessage());
        }
    }

    /**
     * Import static IPs from customers
     */
    public function importFromCustomers(): int
    {
        $customers = Customer::whereNotNull('static_ip')
            ->where('static_ip', '!=', '')
            ->get();

        $imported = 0;

        foreach ($customers as $customer) {
            $exists = IpMonitor::where('ip_address', $customer->static_ip)->exists();
            
            if (!$exists) {
                IpMonitor::create([
                    'ip_address' => $customer->static_ip,
                    'name' => $customer->name,
                    'customer_id' => $customer->id,
                    'is_active' => true,
                    'alert_enabled' => true,
                ]);
                $imported++;
            }
        }

        return $imported;
    }

    /**
     * Get uptime statistics for a monitor
     */
    public function getUptimeStats(IpMonitor $monitor, int $days = 30): array
    {
        $logs = IpMonitorLog::where('ip_monitor_id', $monitor->id)
            ->where('checked_at', '>=', now()->subDays($days))
            ->get();

        $total = $logs->count();
        $up = $logs->where('status', 'up')->count();
        $down = $logs->where('status', 'down')->count();

        $avgLatency = $logs->where('status', 'up')->avg('latency_ms');

        return [
            'total_checks' => $total,
            'up_count' => $up,
            'down_count' => $down,
            'uptime_percent' => $total > 0 ? round(($up / $total) * 100, 2) : 100,
            'avg_latency_ms' => $avgLatency ? round($avgLatency) : null,
            'period_days' => $days,
        ];
    }

    /**
     * Get daily uptime data for chart
     */
    public function getDailyUptimeData(IpMonitor $monitor, int $days = 7): array
    {
        $data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $logs = IpMonitorLog::where('ip_monitor_id', $monitor->id)
                ->whereDate('checked_at', $date)
                ->get();

            $total = $logs->count();
            $up = $logs->where('status', 'up')->count();

            $data[] = [
                'date' => $date,
                'label' => now()->subDays($i)->format('d M'),
                'uptime' => $total > 0 ? round(($up / $total) * 100, 2) : 100,
                'avg_latency' => $logs->where('status', 'up')->avg('latency_ms') ?? 0,
            ];
        }

        return $data;
    }
}
