<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MikrotikService;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = \App\Models\Package::withCount('customers')->latest()->get();
        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('admin.packages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'speed' => 'nullable|string|max:50',
            'price' => 'required|integer|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'pppoe_profile' => 'nullable|string|max:255',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['tax_rate'] = $validated['tax_rate'] ?? 11.0;

        $package = \App\Models\Package::create($validated);

        // Sync PPPoE profile to Mikrotik if profile name provided
        if (!empty($validated['pppoe_profile'])) {
            try {
                $mikrotik = app(MikrotikService::class);
                if ($mikrotik->isConnected()) {
                    // Parse speed for rate limit (e.g., "10M/10M" or "10 Mbps")
                    $rateLimit = $this->parseSpeedToRateLimit($validated['speed'] ?? '');
                    
                    $mikrotik->createPPPoEProfile([
                        'name' => $validated['pppoe_profile'],
                        'rate_limit' => $rateLimit,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Mikrotik profile sync failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.packages.index')
            ->with('success', 'Package created successfully!');
    }

    /**
     * Parse speed string to Mikrotik rate limit format
     */
    private function parseSpeedToRateLimit($speed)
    {
        if (empty($speed)) {
            return null;
        }
        
        // If already in format like "10M/10M", return as is
        if (preg_match('/^\d+[KMG]\/\d+[KMG]$/i', $speed)) {
            return $speed;
        }
        
        // Extract number from string like "10 Mbps", "20Mbps", "10M"
        if (preg_match('/(\d+)\s*[Mm]/', $speed, $matches)) {
            $mbps = $matches[1];
            return "{$mbps}M/{$mbps}M";
        }
        
        return null;
    }

    public function show(\App\Models\Package $package)
    {
        $package->loadCount('customers');
        $customers = $package->customers()->latest()->take(10)->get();
        
        $stats = [
            'total_customers' => $package->customers()->count(),
            'active_customers' => $package->customers()->where('status', 'active')->count(),
            'monthly_revenue' => $package->customers()->where('status', 'active')->count() * $package->price,
        ];
        
        return view('admin.packages.show', compact('package', 'customers', 'stats'));
    }

    public function edit(\App\Models\Package $package)
    {
        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, \App\Models\Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'speed' => 'nullable|string|max:50',
            'price' => 'required|integer|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'pppoe_profile' => 'nullable|string|max:255',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $package->update($validated);

        return redirect()->route('admin.packages.index')
            ->with('success', 'Package updated successfully!');
    }

    public function destroy(\App\Models\Package $package)
    {
        if ($package->customers()->count() > 0) {
            return redirect()->route('admin.packages.index')
                ->with('error', 'Cannot delete package with active customers!');
        }

        $package->delete();

        return redirect()->route('admin.packages.index')
            ->with('success', 'Package deleted successfully!');
    }
}
