<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MikrotikService;
use App\Models\Customer;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MikrotikSyncController extends Controller
{
    protected $mikrotik;

    public function __construct(MikrotikService $mikrotik)
    {
        $this->mikrotik = $mikrotik;
    }

    /**
     * Main sync dashboard
     */
    public function index()
    {
        $connected = $this->mikrotik->isConnected();
        
        if (!$connected) {
            return view('admin.mikrotik.sync.index', [
                'connected' => false,
                'error' => 'Tidak dapat terhubung ke Mikrotik. Silakan cek konfigurasi.',
            ]);
        }

        // Get counts from Mikrotik
        $pppoeSecrets = $this->mikrotik->getPPPoESecrets();
        $pppoeProfiles = $this->mikrotik->getPPPoEProfiles();
        $hotspotUsers = $this->mikrotik->getHotspotUsers();
        $hotspotProfiles = $this->mikrotik->getHotspotProfiles();

        // Get counts from GEMBOK LARA
        $localCustomers = Customer::count();
        $localPackages = Package::count();

        return view('admin.mikrotik.sync.index', [
            'connected' => true,
            'stats' => [
                'pppoe_secrets' => count($pppoeSecrets),
                'pppoe_profiles' => count($pppoeProfiles),
                'hotspot_users' => count($hotspotUsers),
                'hotspot_profiles' => count($hotspotProfiles),
                'local_customers' => $localCustomers,
                'local_packages' => $localPackages,
            ],
        ]);
    }

    /**
     * Show PPPoE Profiles sync page
     */
    public function profiles()
    {
        if (!$this->mikrotik->isConnected()) {
            return redirect()->route('admin.mikrotik.sync.index')
                ->with('error', 'Tidak dapat terhubung ke Mikrotik');
        }

        $mikrotikProfiles = $this->mikrotik->getPPPoEProfiles();
        $localPackages = Package::all();

        // Check which profiles are already mapped
        foreach ($mikrotikProfiles as &$profile) {
            $mapped = Package::where('pppoe_profile', $profile['name'])
                ->orWhere('mikrotik_profile', $profile['name'])
                ->first();
            $profile['mapped_to'] = $mapped ? $mapped->name : null;
            $profile['mapped_package_id'] = $mapped ? $mapped->id : null;
            
            // Parse rate limit
            $speeds = $this->mikrotik->parseRateLimit($profile['rate_limit']);
            $profile['speed_mbps'] = max($speeds['upload'], $speeds['download']);
        }

        return view('admin.mikrotik.sync.profiles', [
            'mikrotikProfiles' => $mikrotikProfiles,
            'localPackages' => $localPackages,
        ]);
    }

    /**
     * Sync profiles to packages
     */
    public function syncProfiles(Request $request)
    {
        $mappings = $request->input('mappings', []);
        $createNew = $request->input('create_new', []);
        $prices = $request->input('prices', []);

        $synced = 0;
        $created = 0;

        DB::beginTransaction();
        try {
            // Update existing package mappings
            foreach ($mappings as $profileName => $packageId) {
                if (!empty($packageId)) {
                    Package::where('id', $packageId)->update([
                        'pppoe_profile' => $profileName,
                        'mikrotik_profile' => $profileName,
                    ]);
                    $synced++;
                }
            }

            // Create new packages from profiles
            $mikrotikProfiles = collect($this->mikrotik->getPPPoEProfiles())
                ->keyBy('name');

            foreach ($createNew as $profileName) {
                $profile = $mikrotikProfiles->get($profileName);
                if ($profile) {
                    $speeds = $this->mikrotik->parseRateLimit($profile['rate_limit']);
                    $speedMbps = max($speeds['upload'], $speeds['download']);
                    
                    Package::create([
                        'name' => $profileName,
                        'speed' => $speedMbps > 0 ? $speedMbps . ' Mbps' : 'Unlimited',
                        'pppoe_profile' => $profileName,
                        'mikrotik_profile' => $profileName,
                        'price' => $prices[$profileName] ?? 0,
                        'is_active' => true,
                        'description' => 'Imported from Mikrotik',
                    ]);
                    $created++;
                }
            }

            DB::commit();

            return redirect()->route('admin.mikrotik.sync.profiles')
                ->with('success', "Berhasil sync {$synced} profile dan membuat {$created} paket baru");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profile sync failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal sync profile: ' . $e->getMessage());
        }
    }

    /**
     * Preview PPPoE Secrets for import
     */
    public function previewSecrets(Request $request)
    {
        if (!$this->mikrotik->isConnected()) {
            return redirect()->route('admin.mikrotik.sync.index')
                ->with('error', 'Tidak dapat terhubung ke Mikrotik');
        }

        $secrets = $this->mikrotik->getPPPoESecrets();
        $localPackages = Package::all()->keyBy('pppoe_profile');
        
        // Get existing PPPoE usernames
        $existingUsernames = Customer::whereNotNull('pppoe_username')
            ->pluck('pppoe_username')
            ->toArray();

        // Process secrets
        $toImport = [];
        $existing = [];
        
        foreach ($secrets as $secret) {
            // Skip non-pppoe services
            if ($secret['service'] !== 'pppoe' && $secret['service'] !== 'any') {
                continue;
            }

            $secret['exists'] = in_array($secret['name'], $existingUsernames);
            $secret['package'] = $localPackages->get($secret['profile']);
            
            if ($secret['exists']) {
                $existing[] = $secret;
            } else {
                $toImport[] = $secret;
            }
        }

        return view('admin.mikrotik.sync.secrets', [
            'toImport' => $toImport,
            'existing' => $existing,
            'localPackages' => Package::all(),
            'totalSecrets' => count($secrets),
        ]);
    }

    /**
     * Import PPPoE Secrets as Customers
     */
    public function importSecrets(Request $request)
    {
        $selectedSecrets = $request->input('secrets', []);
        $defaultPackageId = $request->input('default_package_id');
        $skipExisting = $request->input('skip_existing', true);

        if (empty($selectedSecrets)) {
            return back()->with('error', 'Tidak ada secret yang dipilih');
        }

        $secrets = collect($this->mikrotik->getPPPoESecrets())
            ->keyBy('name');
        
        $localPackages = Package::all()->keyBy('pppoe_profile');
        $existingUsernames = Customer::whereNotNull('pppoe_username')
            ->pluck('pppoe_username')
            ->toArray();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($selectedSecrets as $secretName) {
                $secret = $secrets->get($secretName);
                if (!$secret) continue;

                // Skip if exists
                if ($skipExisting && in_array($secret['name'], $existingUsernames)) {
                    $skipped++;
                    continue;
                }

                // Find package by profile
                $package = $localPackages->get($secret['profile']);
                $packageId = $package ? $package->id : $defaultPackageId;

                // Parse name from comment or username
                $customerName = $this->parseCustomerName($secret['comment'], $secret['name']);

                // Create customer
                Customer::updateOrCreate(
                    ['pppoe_username' => $secret['name']],
                    [
                        'username' => $secret['name'],
                        'pppoe_password' => $secret['password'],
                        'name' => $customerName,
                        'package_id' => $packageId,
                        'static_ip' => $secret['remote_address'],
                        'status' => $secret['disabled'] ? 'suspended' : 'active',
                        'join_date' => now(),
                    ]
                );
                $imported++;
            }

            DB::commit();

            $message = "Berhasil import {$imported} customer";
            if ($skipped > 0) {
                $message .= ", {$skipped} dilewati (sudah ada)";
            }

            return redirect()->route('admin.mikrotik.sync.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Secret import failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    /**
     * Preview Hotspot Users for import
     */
    public function previewHotspot(Request $request)
    {
        if (!$this->mikrotik->isConnected()) {
            return redirect()->route('admin.mikrotik.sync.index')
                ->with('error', 'Tidak dapat terhubung ke Mikrotik');
        }

        $users = $this->mikrotik->getHotspotUsers();
        $localPackages = Package::all()->keyBy('hotspot_profile');
        
        // Get existing usernames
        $existingUsernames = Customer::whereNotNull('username')
            ->pluck('username')
            ->toArray();

        $toImport = [];
        $existing = [];
        
        foreach ($users as $user) {
            $user['exists'] = in_array($user['name'], $existingUsernames);
            $user['package'] = $localPackages->get($user['profile']);
            
            if ($user['exists']) {
                $existing[] = $user;
            } else {
                $toImport[] = $user;
            }
        }

        return view('admin.mikrotik.sync.hotspot', [
            'toImport' => $toImport,
            'existing' => $existing,
            'localPackages' => Package::all(),
            'totalUsers' => count($users),
        ]);
    }

    /**
     * Import Hotspot Users as Customers
     */
    public function importHotspot(Request $request)
    {
        $selectedUsers = $request->input('users', []);
        $defaultPackageId = $request->input('default_package_id');
        $skipExisting = $request->input('skip_existing', true);

        if (empty($selectedUsers)) {
            return back()->with('error', 'Tidak ada user yang dipilih');
        }

        $users = collect($this->mikrotik->getHotspotUsers())
            ->keyBy('name');
        
        $localPackages = Package::all()->keyBy('hotspot_profile');
        $existingUsernames = Customer::pluck('username')->toArray();

        $imported = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($selectedUsers as $userName) {
                $user = $users->get($userName);
                if (!$user) continue;

                if ($skipExisting && in_array($user['name'], $existingUsernames)) {
                    $skipped++;
                    continue;
                }

                $package = $localPackages->get($user['profile']);
                $packageId = $package ? $package->id : $defaultPackageId;

                $customerName = $this->parseCustomerName($user['comment'], $user['name']);

                Customer::updateOrCreate(
                    ['username' => $user['name']],
                    [
                        'pppoe_username' => null,
                        'pppoe_password' => $user['password'],
                        'name' => $customerName,
                        'package_id' => $packageId,
                        'mac_address' => $user['mac_address'],
                        'status' => $user['disabled'] ? 'suspended' : 'active',
                        'join_date' => now(),
                    ]
                );
                $imported++;
            }

            DB::commit();

            $message = "Berhasil import {$imported} customer hotspot";
            if ($skipped > 0) {
                $message .= ", {$skipped} dilewati (sudah ada)";
            }

            return redirect()->route('admin.mikrotik.sync.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Hotspot import failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    /**
     * Parse customer name from comment or username
     */
    private function parseCustomerName($comment, $username)
    {
        if (!empty($comment)) {
            // Try to extract name from comment
            // Common formats: "John Doe - 08123456789" or "John Doe"
            $parts = explode('-', $comment);
            $name = trim($parts[0]);
            if (!empty($name)) {
                return $name;
            }
        }

        // Clean username to make it readable
        $name = str_replace(['pppoe-', 'ppp-', '_', '-'], [' ', ' ', ' ', ' '], $username);
        return ucwords(trim($name));
    }
}
