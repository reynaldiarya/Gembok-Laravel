<?php

namespace App\Services;

use App\Models\Olt;
use App\Models\Onu;
use App\Models\OltPonPort;
use App\Models\OltFan;
use App\Models\OnuStatusLog;
use App\Models\IntegrationSetting;

class OltService
{
    protected $snmpTimeout = 5;
    protected $snmpRetries = 1;

    /**
     * Test connection to OLT via SNMP
     */
    public function testConnection(Olt $olt): array
    {
        try {
            $result = @snmp2_get(
                $olt->ip_address . ':' . $olt->snmp_port,
                $olt->snmp_community,
                '.1.3.6.1.2.1.1.1.0', // sysDescr
                $this->snmpTimeout * 1000000,
                $this->snmpRetries
            );

            if ($result !== false) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'sysDescr' => $this->cleanSnmpValue($result)
                ];
            }

            return ['success' => false, 'message' => 'SNMP request failed'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sync OLT data - get system info, temperature, uptime
     */
    public function syncOlt(Olt $olt): array
    {
        try {
            $data = [];

            // Get system uptime
            $uptime = @snmp2_get(
                $olt->ip_address . ':' . $olt->snmp_port,
                $olt->snmp_community,
                '.1.3.6.1.2.1.1.3.0', // sysUpTime
                $this->snmpTimeout * 1000000,
                $this->snmpRetries
            );

            if ($uptime !== false) {
                $data['uptime'] = $this->formatUptime($this->cleanSnmpValue($uptime));
            }

            // Update ONU counts
            $onuCounts = $this->getOnuCounts($olt);
            $data = array_merge($data, $onuCounts);

            $data['last_sync'] = now();
            $data['status'] = 'online';

            $olt->update($data);

            return ['success' => true, 'message' => 'OLT synced successfully', 'data' => $data];
        } catch (\Exception $e) {
            $olt->update(['status' => 'offline']);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get ONU counts by status
     */
    protected function getOnuCounts(Olt $olt): array
    {
        return [
            'total_onus' => $olt->onus()->count(),
            'online_onus' => $olt->onus()->where('status', 'online')->count(),
            'offline_onus' => $olt->onus()->where('status', 'offline')->count(),
            'los_onus' => $olt->onus()->where('status', 'los')->count(),
            'dyinggasp_onus' => $olt->onus()->where('status', 'dyinggasp')->count(),
        ];
    }

    /**
     * Discover ONUs on OLT (simulation for demo)
     */
    public function discoverOnus(Olt $olt): array
    {
        // In real implementation, this would use SNMP walk or Telnet
        // to discover all ONUs on the OLT
        
        return [
            'success' => true,
            'message' => 'Discovery completed',
            'discovered' => 0,
            'updated' => 0
        ];
    }

    /**
     * Update ONU status
     */
    public function updateOnuStatus(Onu $onu, string $newStatus, ?string $reason = null): void
    {
        $oldStatus = $onu->status;

        if ($oldStatus !== $newStatus) {
            // Log status change
            OnuStatusLog::create([
                'onu_id' => $onu->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason
            ]);

            // Update timestamps
            $updateData = ['status' => $newStatus];
            
            if ($newStatus === 'online') {
                $updateData['last_online'] = now();
                $updateData['offline_reason'] = null;
            } else {
                $updateData['last_offline'] = now();
                $updateData['offline_reason'] = $reason;
            }

            $onu->update($updateData);

            // Update OLT counts
            $this->updateOltCounts($onu->olt);
        }
    }

    /**
     * Update OLT ONU counts
     */
    public function updateOltCounts(Olt $olt): void
    {
        $olt->update($this->getOnuCounts($olt));
    }

    /**
     * Reboot ONU via Telnet (placeholder)
     */
    public function rebootOnu(Onu $onu): array
    {
        // In real implementation, this would connect via Telnet
        // and send reboot command based on OLT brand
        
        return [
            'success' => true,
            'message' => 'Reboot command sent to ONU ' . $onu->serial_number
        ];
    }

    /**
     * Get ONU optical info
     */
    public function getOnuOpticalInfo(Onu $onu): array
    {
        return [
            'rx_power' => $onu->rx_power,
            'tx_power' => $onu->tx_power,
            'temperature' => $onu->temperature,
            'voltage' => $onu->voltage,
            'rx_status' => $onu->rx_power_status
        ];
    }

    /**
     * Clean SNMP value
     */
    protected function cleanSnmpValue($value): string
    {
        $value = preg_replace('/^STRING:\s*"?|"?$/', '', $value);
        $value = preg_replace('/^INTEGER:\s*/', '', $value);
        $value = preg_replace('/^Timeticks:\s*\([0-9]+\)\s*/', '', $value);
        return trim($value);
    }

    /**
     * Format uptime from timeticks
     */
    protected function formatUptime($timeticks): string
    {
        // If already formatted string
        if (preg_match('/\d+\s+days?/', $timeticks)) {
            return $timeticks;
        }

        // Convert timeticks (1/100 seconds) to readable format
        $seconds = intval($timeticks) / 100;
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return "{$days} days {$hours} hours {$minutes} minutes";
    }
}
