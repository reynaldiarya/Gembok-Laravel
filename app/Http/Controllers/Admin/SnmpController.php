<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SnmpService;
use App\Models\NetworkDevice;
use Illuminate\Http\Request;

class SnmpController extends Controller
{
    protected $snmp;

    public function __construct(SnmpService $snmp)
    {
        $this->snmp = $snmp;
    }

    public function index()
    {
        $enabled = $this->snmp->isEnabled();
        
        // Get devices from database
        $devices = NetworkDevice::active()->orderBy('name')->get();
        
        return view('admin.snmp.index', compact('enabled', 'devices'));
    }

    public function device($host)
    {
        if (!$this->snmp->isEnabled()) {
            return back()->with('error', 'SNMP tidak aktif');
        }

        $device = NetworkDevice::where('host', $host)->first();
        $systemInfo = $this->snmp->getSystemInfo($host);
        $interfaces = $this->snmp->getInterfaces($host);
        $resources = $this->snmp->getResourceUsage($host);

        // Update device status
        if ($device) {
            $online = !isset($systemInfo['error']);
            $device->update([
                'status' => $online ? 'online' : 'offline',
                'last_check' => now(),
                'cpu_usage' => $resources['cpu_usage'] ?? null,
                'memory_usage' => $resources['memory_percent'] ?? null,
            ]);
        }

        return view('admin.snmp.device', compact('host', 'device', 'systemInfo', 'interfaces', 'resources'));
    }

    public function traffic(Request $request)
    {
        $host = $request->get('host');
        $ifIndex = $request->get('interface', 1);

        if (!$this->snmp->isEnabled()) {
            return response()->json(['error' => 'SNMP not enabled']);
        }

        $stats = $this->snmp->getTrafficStats($host, $ifIndex);
        return response()->json($stats);
    }

    public function ping(Request $request)
    {
        $host = $request->get('host');
        $result = $this->snmp->ping($host);
        
        // Update device status in database
        NetworkDevice::where('host', $host)->update([
            'status' => $result ? 'online' : 'offline',
            'last_check' => now(),
        ]);
        
        return response()->json(['online' => $result]);
    }

    public function storeDevice(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'host' => 'required|ip',
            'community' => 'nullable|string|max:50',
            'type' => 'required|in:router,switch,olt,server,ap,other',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Check if host already exists
        if (NetworkDevice::where('host', $request->host)->exists()) {
            return back()->with('error', 'IP address sudah terdaftar')->withInput();
        }

        NetworkDevice::create([
            'name' => $request->name,
            'host' => $request->host,
            'community' => $request->community ?? 'public',
            'type' => $request->type,
            'location' => $request->location,
            'description' => $request->description,
            'is_active' => true,
            'snmp_enabled' => true,
        ]);

        return back()->with('success', 'Perangkat berhasil ditambahkan');
    }

    public function deleteDevice($id)
    {
        $device = NetworkDevice::findOrFail($id);
        $device->delete();

        return back()->with('success', 'Perangkat berhasil dihapus');
    }

    public function dashboard()
    {
        if (!$this->snmp->isEnabled()) {
            return view('admin.snmp.dashboard', ['enabled' => false, 'devices' => []]);
        }

        $devices = NetworkDevice::active()->get();
        $deviceStatus = [];

        foreach ($devices as $device) {
            $online = $this->snmp->ping($device->host);
            $system = $online ? $this->snmp->getSystemInfo($device->host) : null;
            
            // Update device status
            $device->update([
                'status' => $online ? 'online' : 'offline',
                'last_check' => now(),
            ]);

            $deviceStatus[] = [
                'device' => $device,
                'online' => $online,
                'system' => $system,
            ];
        }

        return view('admin.snmp.dashboard', [
            'enabled' => true,
            'devices' => $deviceStatus,
        ]);
    }
}
