<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IpMonitorService;
use App\Models\IpMonitor;

class CheckIpMonitors extends Command
{
    protected $signature = 'ip-monitor:check {--all : Check all monitors regardless of interval}';
    protected $description = 'Check all active IP monitors';

    public function handle(IpMonitorService $service)
    {
        $this->info('Starting IP monitor check...');

        if ($this->option('all')) {
            $monitors = IpMonitor::active()->get();
            $this->info("Checking {$monitors->count()} monitors...");

            $up = 0;
            $down = 0;

            foreach ($monitors as $monitor) {
                $result = $service->checkMonitor($monitor);
                if ($result['status'] === 'up') {
                    $up++;
                    $this->line("<fg=green>✓</> {$monitor->ip_address} - UP ({$result['latency_ms']}ms)");
                } else {
                    $down++;
                    $this->line("<fg=red>✗</> {$monitor->ip_address} - DOWN");
                }
            }

            $this->newLine();
            $this->info("Results: {$up} UP, {$down} DOWN");
        } else {
            $results = $service->checkAllMonitors();
            $this->info("Checked " . count($results) . " monitors (based on interval)");
        }

        $this->info('IP monitor check completed.');
        return Command::SUCCESS;
    }
}
