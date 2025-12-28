<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpMonitor;
use App\Models\IpMonitorLog;
use App\Models\NetworkAlert;
use App\Models\Customer;
use App\Models\NetworkDevice;
use App\Services\IpMonitorService;
use Illuminate\Http\Request;

class IpMonitorController extends Controller
{
    protected $monitorService;

    public function __construct(IpMonitorService $monitorService)
    {
        $this->monitorService = $monitorService;
    }

    public function index(Request $request)
    {
        $query = IpMonitor::with(['customer', 'networkDevice']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by active
        if ($request->filled('active')) {
            $query->where('is_active', $request->active === 'yes');
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $monitors = $query->orderBy('status', 'desc')->orderBy('name')->paginate(20);

        // Stats
        $stats = [
            'total' => IpMonitor::count(),
            'active' => IpMonitor::active()->count(),
            'up' => IpMonitor::active()->up()->count(),
            'down' => IpMonitor::active()->down()->count(),
        ];

        // Recent alerts
        $recentAlerts = NetworkAlert::where('alertable_type', IpMonitor::class)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.ip-monitor.index', compact('monitors', 'stats', 'recentAlerts'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $devices = NetworkDevice::active()->orderBy('name')->get();
        return view('admin.ip-monitor.create', compact('customers', 'devices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'name' => 'nullable|string|max:100',
            'customer_id' => 'nullable|exists:customers,id',
            'network_device_id' => 'nullable|exists:network_devices,id',
            'check_interval' => 'required|integer|min:60|max:3600',
            'alert_threshold' => 'required|integer|min:1|max:10',
        ]);

        // Check if IP already exists
        if (IpMonitor::where('ip_address', $request->ip_address)->exists()) {
            return back()->with('error', 'IP address sudah terdaftar')->withInput();
        }

        IpMonitor::create([
            'ip_address' => $request->ip_address,
            'name' => $request->name,
            'customer_id' => $request->customer_id,
            'network_device_id' => $request->network_device_id,
            'check_interval' => $request->check_interval,
            'alert_threshold' => $request->alert_threshold,
            'is_active' => $request->has('is_active'),
            'alert_enabled' => $request->has('alert_enabled'),
        ]);

        return redirect()->route('admin.ip-monitor.index')->with('success', 'IP Monitor berhasil ditambahkan');
    }


    public function show(IpMonitor $ipMonitor)
    {
        $ipMonitor->load(['customer', 'networkDevice', 'alerts' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(20);
        }]);

        // Get uptime stats
        $uptimeStats = $this->monitorService->getUptimeStats($ipMonitor, 30);
        $dailyData = $this->monitorService->getDailyUptimeData($ipMonitor, 7);

        // Recent logs
        $recentLogs = IpMonitorLog::where('ip_monitor_id', $ipMonitor->id)
            ->orderBy('checked_at', 'desc')
            ->limit(50)
            ->get();

        return view('admin.ip-monitor.show', compact('ipMonitor', 'uptimeStats', 'dailyData', 'recentLogs'));
    }

    public function edit(IpMonitor $ipMonitor)
    {
        $customers = Customer::orderBy('name')->get();
        $devices = NetworkDevice::active()->orderBy('name')->get();
        return view('admin.ip-monitor.edit', compact('ipMonitor', 'customers', 'devices'));
    }

    public function update(Request $request, IpMonitor $ipMonitor)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'name' => 'nullable|string|max:100',
            'customer_id' => 'nullable|exists:customers,id',
            'network_device_id' => 'nullable|exists:network_devices,id',
            'check_interval' => 'required|integer|min:60|max:3600',
            'alert_threshold' => 'required|integer|min:1|max:10',
        ]);

        // Check if IP already exists (exclude current)
        if (IpMonitor::where('ip_address', $request->ip_address)->where('id', '!=', $ipMonitor->id)->exists()) {
            return back()->with('error', 'IP address sudah terdaftar')->withInput();
        }

        $ipMonitor->update([
            'ip_address' => $request->ip_address,
            'name' => $request->name,
            'customer_id' => $request->customer_id,
            'network_device_id' => $request->network_device_id,
            'check_interval' => $request->check_interval,
            'alert_threshold' => $request->alert_threshold,
            'is_active' => $request->has('is_active'),
            'alert_enabled' => $request->has('alert_enabled'),
        ]);

        return redirect()->route('admin.ip-monitor.show', $ipMonitor)->with('success', 'IP Monitor berhasil diupdate');
    }

    public function destroy(IpMonitor $ipMonitor)
    {
        $ipMonitor->delete();
        return redirect()->route('admin.ip-monitor.index')->with('success', 'IP Monitor berhasil dihapus');
    }

    public function ping(IpMonitor $ipMonitor)
    {
        $result = $this->monitorService->checkMonitor($ipMonitor);
        return response()->json([
            'success' => true,
            'result' => $result,
            'monitor' => $ipMonitor->fresh(),
        ]);
    }

    public function pingAll()
    {
        $results = $this->monitorService->checkAllMonitors();
        return response()->json([
            'success' => true,
            'checked' => count($results),
            'results' => $results,
        ]);
    }

    public function importCustomers()
    {
        $imported = $this->monitorService->importFromCustomers();
        return back()->with('success', "Berhasil import {$imported} IP dari data customer");
    }

    public function toggleActive(IpMonitor $ipMonitor)
    {
        $ipMonitor->update(['is_active' => !$ipMonitor->is_active]);
        $status = $ipMonitor->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Monitor {$ipMonitor->ip_address} berhasil {$status}");
    }

    public function alerts(Request $request)
    {
        $query = NetworkAlert::where('alertable_type', IpMonitor::class)
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('unread')) {
            $query->unread();
        }

        $alerts = $query->paginate(30);

        return view('admin.ip-monitor.alerts', compact('alerts'));
    }

    public function markAlertRead(NetworkAlert $alert)
    {
        $alert->update(['is_read' => true]);
        return back()->with('success', 'Alert ditandai sudah dibaca');
    }

    public function markAllAlertsRead()
    {
        NetworkAlert::where('alertable_type', IpMonitor::class)
            ->unread()
            ->update(['is_read' => true]);
        return back()->with('success', 'Semua alert ditandai sudah dibaca');
    }

    public function dashboard()
    {
        $stats = [
            'total' => IpMonitor::count(),
            'active' => IpMonitor::active()->count(),
            'up' => IpMonitor::active()->up()->count(),
            'down' => IpMonitor::active()->down()->count(),
            'unread_alerts' => NetworkAlert::where('alertable_type', IpMonitor::class)->unread()->count(),
        ];

        $downMonitors = IpMonitor::active()->down()->with('customer')->get();
        $recentAlerts = NetworkAlert::where('alertable_type', IpMonitor::class)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.ip-monitor.dashboard', compact('stats', 'downMonitors', 'recentAlerts'));
    }
}
