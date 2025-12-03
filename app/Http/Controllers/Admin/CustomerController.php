<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Events\CustomerSuspended;
use App\Services\MikrotikService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    protected $mikrotik;

    public function index(Request $request)
    {
        $query = \App\Models\Customer::with('package');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by package
        if ($request->filled('package_id')) {
            $query->where('package_id', $request->package_id);
        }

        $customers = $query->latest()->paginate(20);
        $packages = \App\Models\Package::where('is_active', true)->get();

        return view('admin.customers.index', compact('customers', 'packages'));
    }

    public function create()
    {
        $packages = \App\Models\Package::where('is_active', true)->get();
        return view('admin.customers.create', compact('packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:customers,username',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'package_id' => 'nullable|exists:packages,id',
            'status' => 'required|in:active,inactive,suspended',
            'pppoe_username' => 'nullable|string|max:255',
            'pppoe_password' => 'nullable|string|max:255',
        ]);

        $validated['join_date'] = now();

        $customer = \App\Models\Customer::create($validated);

        // Sync to Mikrotik if PPPoE credentials provided
        if (!empty($validated['pppoe_username']) && $validated['status'] === 'active') {
            try {
                $mikrotik = app(MikrotikService::class);
                if ($mikrotik->isConnected()) {
                    $package = $customer->package;
                    $mikrotik->createPPPoESecret([
                        'username' => $validated['pppoe_username'],
                        'password' => $validated['pppoe_password'] ?? $validated['pppoe_username'],
                        'profile' => $package->pppoe_profile ?? 'default',
                        'comment' => "Customer: {$customer->name} (ID: {$customer->id})",
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Mikrotik sync failed on customer create: ' . $e->getMessage());
                return redirect()->route('admin.customers.index')
                    ->with('warning', 'Customer created but Mikrotik sync failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer created successfully!');
    }

    public function show(\App\Models\Customer $customer)
    {
        $customer->load(['package', 'invoices', 'cableRoutes', 'onuDevices']);
        
        $stats = [
            'total_invoices' => $customer->invoices()->count(),
            'paid_invoices' => $customer->invoices()->where('status', 'paid')->count(),
            'unpaid_invoices' => $customer->invoices()->where('status', 'unpaid')->count(),
            'total_paid' => $customer->invoices()->where('status', 'paid')->sum('amount'),
            'total_unpaid' => $customer->invoices()->where('status', 'unpaid')->sum('amount'),
        ];

        return view('admin.customers.show', compact('customer', 'stats'));
    }

    public function edit(\App\Models\Customer $customer)
    {
        $packages = \App\Models\Package::where('is_active', true)->get();
        return view('admin.customers.edit', compact('customer', 'packages'));
    }

    public function update(Request $request, \App\Models\Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:customers,username,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'package_id' => 'nullable|exists:packages,id',
            'status' => 'required|in:active,inactive,suspended',
            'pppoe_username' => 'nullable|string|max:255',
            'pppoe_password' => 'nullable|string|max:255',
        ]);

        $oldStatus = $customer->status;
        $customer->update($validated);

        // Fire event if customer is suspended
        if ($oldStatus !== 'suspended' && $validated['status'] === 'suspended') {
            event(new CustomerSuspended($customer));
        }

        // Sync with Mikrotik if PPPoE credentials changed (lazy load)
        if ($customer->pppoe_username && $validated['status'] === 'active') {
            try {
                $mikrotik = app(MikrotikService::class);
                if ($mikrotik->isConnected()) {
                    $mikrotik->createPPPoESecret([
                        'username' => $customer->pppoe_username,
                        'password' => $customer->pppoe_password,
                        'profile' => $customer->package->mikrotik_profile ?? 'default',
                        'comment' => "Customer: {$customer->name}",
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the request
                \Log::warning('Mikrotik sync failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer updated successfully!');
    }

    public function destroy(\App\Models\Customer $customer)
    {
        $customer->delete();

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer deleted successfully!');
    }

    public function invoices(\App\Models\Customer $customer)
    {
        $invoices = $customer->invoices()->with('package')->latest()->paginate(20);
        return view('admin.customers.invoices', compact('customer', 'invoices'));
    }
}
