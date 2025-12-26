<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Olt;
use App\Models\Onu;
use App\Models\OltPonPort;
use App\Models\OltFan;
use App\Models\Customer;
use App\Services\OltService;
use Illuminate\Http\Request;

class OltController extends Controller
{
    protected $oltService;

    public function __construct(OltService $oltService)
    {
        $this->oltService = $oltService;
    }

    /**
     * OLT Dashboard
     */
    public function index()
    {
        $olts = Olt::withCount(['onus', 'ponPorts'])->get();
        
        $stats = [
            'total_olts' => $olts->count(),
            'online_olts' => $olts->where('status', 'online')->count(),
            'total_onus' => Onu::count(),
            'online_onus' => Onu::where('status', 'online')->count(),
            'los_onus' => Onu::where('status', 'los')->count(),
            'dyinggasp_onus' => Onu::where('status', 'dyinggasp')->count(),
            'offline_onus' => Onu::where('status', 'offline')->count(),
        ];

        if ($stats['total_onus'] > 0) {
            $stats['online_percentage'] = round(($stats['online_onus'] / $stats['total_onus']) * 100, 2);
            $stats['los_percentage'] = round(($stats['los_onus'] / $stats['total_onus']) * 100, 2);
        } else {
            $stats['online_percentage'] = 0;
            $stats['los_percentage'] = 0;
        }

        return view('admin.olt.index', compact('olts', 'stats'));
    }

    /**
     * Create OLT form
     */
    public function create()
    {
        return view('admin.olt.create');
    }

    /**
     * Store new OLT
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:100',
            'model' => 'nullable|string|max:100',
            'ip_address' => 'required|ip',
            'snmp_port' => 'integer|min:1|max:65535',
            'snmp_community' => 'required|string|max:100',
            'total_pon_ports' => 'integer|min:1|max:128',
        ]);

        $olt = Olt::create($request->all());

        // Create PON ports
        for ($i = 1; $i <= $request->total_pon_ports; $i++) {
            OltPonPort::create([
                'olt_id' => $olt->id,
                'port_name' => "gpon-olt_1/1/{$i}",
                'slot' => 1,
                'port' => $i,
            ]);
        }

        // Create default fans
        for ($i = 1; $i <= 3; $i++) {
            OltFan::create([
                'olt_id' => $olt->id,
                'fan_name' => "Fan {$i}",
                'fan_index' => $i,
                'speed_rpm' => rand(2200, 2500),
                'speed_level' => 'high',
                'status' => 'online'
            ]);
        }

        return redirect()->route('admin.olt.index')->with('success', 'OLT berhasil ditambahkan');
    }

    /**
     * Show OLT detail
     */
    public function show(Olt $olt)
    {
        $olt->load(['ponPorts', 'fans']);
        
        $onus = $olt->onus()
            ->with('customer')
            ->orderByRaw("FIELD(status, 'los', 'dyinggasp', 'offline', 'online', 'unknown')")
            ->paginate(20);

        $statusCounts = [
            'online' => $olt->onus()->where('status', 'online')->count(),
            'offline' => $olt->onus()->where('status', 'offline')->count(),
            'los' => $olt->onus()->where('status', 'los')->count(),
            'dyinggasp' => $olt->onus()->where('status', 'dyinggasp')->count(),
        ];

        return view('admin.olt.show', compact('olt', 'onus', 'statusCounts'));
    }

    /**
     * Edit OLT form
     */
    public function edit(Olt $olt)
    {
        return view('admin.olt.edit', compact('olt'));
    }

    /**
     * Update OLT
     */
    public function update(Request $request, Olt $olt)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:100',
            'ip_address' => 'required|ip',
            'snmp_community' => 'required|string|max:100',
        ]);

        $olt->update($request->all());

        return redirect()->route('admin.olt.show', $olt)->with('success', 'OLT berhasil diupdate');
    }

    /**
     * Delete OLT
     */
    public function destroy(Olt $olt)
    {
        $olt->delete();
        return redirect()->route('admin.olt.index')->with('success', 'OLT berhasil dihapus');
    }

    /**
     * Test OLT connection
     */
    public function testConnection(Olt $olt)
    {
        $result = $this->oltService->testConnection($olt);
        return response()->json($result);
    }

    /**
     * Sync OLT data
     */
    public function sync(Olt $olt)
    {
        $result = $this->oltService->syncOlt($olt);
        
        if ($result['success']) {
            return back()->with('success', 'OLT berhasil di-sync');
        }
        
        return back()->with('error', 'Gagal sync OLT: ' . $result['message']);
    }

    /**
     * ONU List
     */
    public function onuIndex(Request $request)
    {
        $query = Onu::with(['olt', 'customer']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%")
                  ->orWhereHas('customer', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('olt_id')) {
            $query->where('olt_id', $request->olt_id);
        }

        $onus = $query->orderByRaw("FIELD(status, 'los', 'dyinggasp', 'offline', 'online', 'unknown')")
            ->paginate(20);

        $olts = Olt::all();

        return view('admin.olt.onu-index', compact('onus', 'olts'));
    }

    /**
     * Show ONU detail
     */
    public function onuShow(Onu $onu)
    {
        $onu->load(['olt', 'customer', 'ponPort', 'statusLogs' => function($q) {
            $q->latest()->limit(20);
        }]);

        $customers = Customer::whereNull('id')
            ->orWhereNotIn('id', Onu::whereNotNull('customer_id')->pluck('customer_id'))
            ->orWhere('id', $onu->customer_id)
            ->get();

        return view('admin.olt.onu-show', compact('onu', 'customers'));
    }

    /**
     * Create ONU form
     */
    public function onuCreate()
    {
        $olts = Olt::with('ponPorts')->get();
        $customers = Customer::whereNotIn('id', Onu::whereNotNull('customer_id')->pluck('customer_id'))->get();
        
        return view('admin.olt.onu-create', compact('olts', 'customers'));
    }

    /**
     * Store new ONU
     */
    public function onuStore(Request $request)
    {
        $request->validate([
            'olt_id' => 'required|exists:olts,id',
            'serial_number' => 'required|string|unique:onus,serial_number',
            'name' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $onu = Onu::create($request->all());

        // Update OLT counts
        $this->oltService->updateOltCounts($onu->olt);

        return redirect()->route('admin.olt.onu.index')->with('success', 'ONU berhasil ditambahkan');
    }

    /**
     * Update ONU
     */
    public function onuUpdate(Request $request, Onu $onu)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $onu->update($request->only(['name', 'customer_id', 'model']));

        return back()->with('success', 'ONU berhasil diupdate');
    }

    /**
     * Delete ONU
     */
    public function onuDestroy(Onu $onu)
    {
        $olt = $onu->olt;
        $onu->delete();
        
        $this->oltService->updateOltCounts($olt);

        return redirect()->route('admin.olt.onu.index')->with('success', 'ONU berhasil dihapus');
    }

    /**
     * Reboot ONU
     */
    public function onuReboot(Onu $onu)
    {
        $result = $this->oltService->rebootOnu($onu);
        return response()->json($result);
    }

    /**
     * Update ONU status manually
     */
    public function onuUpdateStatus(Request $request, Onu $onu)
    {
        $request->validate([
            'status' => 'required|in:online,offline,los,dyinggasp'
        ]);

        $this->oltService->updateOnuStatus($onu, $request->status, 'Manual update');

        return back()->with('success', 'Status ONU berhasil diupdate');
    }
}
