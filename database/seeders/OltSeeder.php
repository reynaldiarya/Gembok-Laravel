<?php

namespace Database\Seeders;

use App\Models\Olt;
use App\Models\OltPonPort;
use App\Models\OltFan;
use App\Models\Onu;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class OltSeeder extends Seeder
{
    public function run(): void
    {
        // Create OLT
        $olt = Olt::create([
            'name' => 'OLT-C300',
            'brand' => 'ZTE',
            'model' => 'C300',
            'ip_address' => '192.168.100.1',
            'snmp_port' => 161,
            'snmp_community' => 'public',
            'snmp_version' => '2c',
            'telnet_username' => 'admin',
            'telnet_password' => 'admin123',
            'telnet_port' => 23,
            'location' => 'Data Center Jakarta',
            'description' => 'Main OLT for Jakarta area',
            'temperature' => 27,
            'total_pon_ports' => 8,
            'uptime' => '9 days 3 hours 58 minutes',
            'status' => 'online',
            'last_sync' => now(),
        ]);

        // Create PON Ports
        for ($i = 1; $i <= 8; $i++) {
            OltPonPort::create([
                'olt_id' => $olt->id,
                'port_name' => "gpon-olt_1/1/{$i}",
                'slot' => 1,
                'port' => $i,
                'total_onus' => 0,
                'online_onus' => 0,
                'status' => 'up',
            ]);
        }

        // Create Fans
        $fans = [
            ['fan_name' => 'Fan 1', 'speed_rpm' => 2300, 'speed_level' => 'high', 'status' => 'online'],
            ['fan_name' => 'Fan 2', 'speed_rpm' => 2360, 'speed_level' => 'high', 'status' => 'online'],
            ['fan_name' => 'Fan 3', 'speed_rpm' => 2300, 'speed_level' => 'high', 'status' => 'online'],
        ];

        foreach ($fans as $index => $fan) {
            OltFan::create([
                'olt_id' => $olt->id,
                'fan_name' => $fan['fan_name'],
                'fan_index' => $index + 1,
                'speed_rpm' => $fan['speed_rpm'],
                'speed_level' => $fan['speed_level'],
                'status' => $fan['status'],
            ]);
        }

        // Create ONUs
        $customers = Customer::all();
        $statuses = ['online', 'online', 'online', 'online', 'online', 'offline', 'los', 'dyinggasp'];
        $models = ['F660', 'F670L', 'HG8245H', 'HG8546M', 'AN5506-04-F'];

        $onuData = [
            ['sn' => 'ZTEGC1234567', 'name' => 'ONU-001', 'status' => 'online', 'rx' => -22.5, 'tx' => 2.1],
            ['sn' => 'ZTEGC2345678', 'name' => 'ONU-002', 'status' => 'online', 'rx' => -23.1, 'tx' => 2.3],
            ['sn' => 'ZTEGC3456789', 'name' => 'ONU-003', 'status' => 'online', 'rx' => -21.8, 'tx' => 2.0],
            ['sn' => 'ZTEGC4567890', 'name' => 'ONU-004', 'status' => 'online', 'rx' => -24.2, 'tx' => 2.2],
            ['sn' => 'ZTEGC5678901', 'name' => 'ONU-005', 'status' => 'online', 'rx' => -22.9, 'tx' => 2.1],
            ['sn' => 'ZTEGC6789012', 'name' => 'ONU-006', 'status' => 'offline', 'rx' => null, 'tx' => null],
            ['sn' => 'ZTEGC7890123', 'name' => 'ONU-007', 'status' => 'los', 'rx' => null, 'tx' => null],
            ['sn' => 'ZTEGC8901234', 'name' => 'ONU-008', 'status' => 'dyinggasp', 'rx' => -26.5, 'tx' => 1.8],
            ['sn' => 'ZTEGC9012345', 'name' => 'ONU-009', 'status' => 'online', 'rx' => -23.5, 'tx' => 2.0],
            ['sn' => 'ZTEGC0123456', 'name' => 'ONU-010', 'status' => 'online', 'rx' => -22.1, 'tx' => 2.2],
        ];

        $ponPorts = OltPonPort::where('olt_id', $olt->id)->get();

        foreach ($onuData as $index => $data) {
            $ponPort = $ponPorts[$index % $ponPorts->count()];
            $customer = $customers->count() > $index ? $customers[$index] : null;

            Onu::create([
                'olt_id' => $olt->id,
                'pon_port_id' => $ponPort->id,
                'customer_id' => $customer?->id,
                'serial_number' => $data['sn'],
                'mac_address' => sprintf('AA:BB:CC:%02X:%02X:%02X', rand(0, 255), rand(0, 255), rand(0, 255)),
                'name' => $data['name'],
                'model' => $models[array_rand($models)],
                'pon_location' => "1/1/" . (($index % 8) + 1) . ":" . ($index + 1),
                'onu_id' => $index + 1,
                'rx_power' => $data['rx'],
                'tx_power' => $data['tx'],
                'temperature' => $data['status'] == 'online' ? rand(35, 45) : null,
                'voltage' => $data['status'] == 'online' ? 3.3 : null,
                'rx_bytes' => rand(1000000000, 50000000000),
                'tx_bytes' => rand(500000000, 10000000000),
                'firmware_version' => 'V5.0.10P2T1',
                'hardware_version' => 'V1.0',
                'ip_address' => $data['status'] == 'online' ? '192.168.1.' . (100 + $index) : null,
                'status' => $data['status'],
                'last_online' => $data['status'] == 'online' ? now() : now()->subHours(rand(1, 48)),
                'last_offline' => $data['status'] != 'online' ? now()->subMinutes(rand(5, 120)) : null,
                'offline_reason' => $data['status'] == 'los' ? 'Loss of Signal' : ($data['status'] == 'dyinggasp' ? 'Power failure detected' : null),
            ]);

            // Update PON port counts
            $ponPort->increment('total_onus');
            if ($data['status'] == 'online') {
                $ponPort->increment('online_onus');
            }
        }

        // Update OLT counts
        $olt->update([
            'total_onus' => Onu::where('olt_id', $olt->id)->count(),
            'online_onus' => Onu::where('olt_id', $olt->id)->where('status', 'online')->count(),
            'offline_onus' => Onu::where('olt_id', $olt->id)->where('status', 'offline')->count(),
            'los_onus' => Onu::where('olt_id', $olt->id)->where('status', 'los')->count(),
            'dyinggasp_onus' => Onu::where('olt_id', $olt->id)->where('status', 'dyinggasp')->count(),
        ]);
    }
}
