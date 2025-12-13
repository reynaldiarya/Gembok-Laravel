<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IntegrationSetting;
use App\Services\MikrotikService;
use App\Services\RadiusService;
use App\Services\GenieAcsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntegrationSettingController extends Controller
{
    public function index()
    {
        $integrations = [
            'mikrotik' => IntegrationSetting::getByType('mikrotik'),
            'radius' => IntegrationSetting::getByType('radius'),
            'genieacs' => IntegrationSetting::getByType('genieacs'),
            'whatsapp' => IntegrationSetting::getByType('whatsapp'),
            'midtrans' => IntegrationSetting::getByType('midtrans'),
            'xendit' => IntegrationSetting::getByType('xendit'),
        ];

        return view('admin.settings.integrations', compact('integrations'));
    }

    // Mikrotik Settings
    public function mikrotik()
    {
        $setting = IntegrationSetting::getByType('mikrotik') ?? new IntegrationSetting(['type' => 'mikrotik']);
        return view('admin.settings.mikrotik', compact('setting'));
    }

    public function saveMikrotik(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'host' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $setting = IntegrationSetting::updateOrCreate(
            ['type' => 'mikrotik'],
            [
                'name' => $request->name ?? 'Mikrotik Router',
                'enabled' => $request->boolean('enabled'),
                'config' => [
                    'host' => $request->host,
                    'port' => (int) $request->port,
                    'username' => $request->username,
                    'password' => $request->password,
                    'use_ssl' => $request->boolean('use_ssl'),
                ],
            ]
        );

        return back()->with('success', 'Konfigurasi Mikrotik berhasil disimpan');
    }

    public function testMikrotik(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $config = [
                'host' => $request->host,
                'port' => (int) $request->port,
                'username' => $request->username,
                'password' => $request->password,
                'use_ssl' => $request->boolean('use_ssl'),
            ];

            $mikrotik = new MikrotikService($config);
            
            if ($mikrotik->connect()) {
                $identity = $mikrotik->getSystemIdentity();
                $resource = $mikrotik->getSystemResource();
                
                // Update test result
                $setting = IntegrationSetting::getByType('mikrotik');
                if ($setting) {
                    $setting->updateTestResult(true, "Connected to: {$identity}");
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Koneksi berhasil!',
                    'data' => [
                        'identity' => $identity,
                        'uptime' => $resource['uptime'] ?? '-',
                        'version' => $resource['version'] ?? '-',
                        'cpu_load' => $resource['cpu-load'] ?? '-',
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal terhubung ke Mikrotik'
            ]);

        } catch (\Exception $e) {
            $setting = IntegrationSetting::getByType('mikrotik');
            if ($setting) {
                $setting->updateTestResult(false, $e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // RADIUS Settings
    public function radius()
    {
        $setting = IntegrationSetting::getByType('radius') ?? new IntegrationSetting(['type' => 'radius']);
        return view('admin.settings.radius', compact('setting'));
    }

    public function saveRadius(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'host' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $setting = IntegrationSetting::updateOrCreate(
            ['type' => 'radius'],
            [
                'name' => $request->name ?? 'FreeRADIUS Server',
                'enabled' => $request->boolean('enabled'),
                'config' => [
                    'host' => $request->host,
                    'port' => (int) $request->port,
                    'database' => $request->database,
                    'username' => $request->username,
                    'password' => $request->password,
                    'nas_secret' => $request->nas_secret ?? 'testing123',
                ],
            ]
        );

        return back()->with('success', 'Konfigurasi RADIUS berhasil disimpan');
    }

    public function testRadius(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|integer',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            // Test database connection
            $connection = @mysqli_connect(
                $request->host,
                $request->username,
                $request->password,
                $request->database,
                (int) $request->port
            );

            if (!$connection) {
                throw new \Exception(mysqli_connect_error());
            }

            // Check if radcheck table exists
            $result = mysqli_query($connection, "SHOW TABLES LIKE 'radcheck'");
            $hasRadcheck = mysqli_num_rows($result) > 0;

            // Count users
            $userCount = 0;
            if ($hasRadcheck) {
                $result = mysqli_query($connection, "SELECT COUNT(DISTINCT username) as count FROM radcheck");
                $row = mysqli_fetch_assoc($result);
                $userCount = $row['count'] ?? 0;
            }

            mysqli_close($connection);

            // Update test result
            $setting = IntegrationSetting::getByType('radius');
            if ($setting) {
                $setting->updateTestResult(true, "Connected. Users: {$userCount}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Koneksi database RADIUS berhasil!',
                'data' => [
                    'has_radcheck' => $hasRadcheck,
                    'user_count' => $userCount,
                ]
            ]);

        } catch (\Exception $e) {
            $setting = IntegrationSetting::getByType('radius');
            if ($setting) {
                $setting->updateTestResult(false, $e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // GenieACS Settings
    public function genieacs()
    {
        $setting = IntegrationSetting::getByType('genieacs') ?? new IntegrationSetting(['type' => 'genieacs']);
        return view('admin.settings.genieacs', compact('setting'));
    }

    public function saveGenieacs(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'url' => 'required|url',
        ]);

        $setting = IntegrationSetting::updateOrCreate(
            ['type' => 'genieacs'],
            [
                'name' => $request->name ?? 'GenieACS Server',
                'enabled' => $request->boolean('enabled'),
                'config' => [
                    'url' => rtrim($request->url, '/'),
                    'username' => $request->username,
                    'password' => $request->password,
                ],
            ]
        );

        return back()->with('success', 'Konfigurasi GenieACS berhasil disimpan');
    }

    public function testGenieacs(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        try {
            $url = rtrim($request->url, '/') . '/devices/?projection=_id';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            if ($request->username) {
                curl_setopt($ch, CURLOPT_USERPWD, $request->username . ':' . $request->password);
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception($error);
            }

            if ($httpCode !== 200) {
                throw new \Exception("HTTP Error: {$httpCode}");
            }

            $devices = json_decode($response, true);
            $deviceCount = is_array($devices) ? count($devices) : 0;

            // Update test result
            $setting = IntegrationSetting::getByType('genieacs');
            if ($setting) {
                $setting->updateTestResult(true, "Connected. Devices: {$deviceCount}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Koneksi GenieACS berhasil!',
                'data' => [
                    'device_count' => $deviceCount,
                ]
            ]);

        } catch (\Exception $e) {
            $setting = IntegrationSetting::getByType('genieacs');
            if ($setting) {
                $setting->updateTestResult(false, $e->getMessage());
            }

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // WhatsApp Settings
    public function whatsapp()
    {
        $setting = IntegrationSetting::getByType('whatsapp') ?? new IntegrationSetting(['type' => 'whatsapp']);
        return view('admin.settings.whatsapp', compact('setting'));
    }

    public function saveWhatsapp(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'api_url' => 'required|url',
            'api_key' => 'required|string',
        ]);

        $setting = IntegrationSetting::updateOrCreate(
            ['type' => 'whatsapp'],
            [
                'name' => $request->name ?? 'WhatsApp Gateway',
                'enabled' => $request->boolean('enabled'),
                'config' => [
                    'api_url' => rtrim($request->api_url, '/'),
                    'api_key' => $request->api_key,
                    'sender' => $request->sender,
                    'admin_phone' => $request->admin_phone,
                ],
            ]
        );

        return back()->with('success', 'Konfigurasi WhatsApp berhasil disimpan');
    }

    public function testWhatsapp(Request $request)
    {
        $request->validate([
            'api_url' => 'required|url',
            'api_key' => 'required|string',
            'test_number' => 'required|string',
        ]);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, rtrim($request->api_url, '/') . '/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'target' => $request->test_number,
                'message' => '🔔 Test koneksi dari GEMBOK LARA - ' . now()->format('d/m/Y H:i:s'),
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: ' . $request->api_key,
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception($error);
            }

            $result = json_decode($response, true);

            // Update test result
            $setting = IntegrationSetting::getByType('whatsapp');
            if ($setting) {
                $setting->updateTestResult($httpCode === 200, $response);
            }

            return response()->json([
                'success' => $httpCode === 200,
                'message' => $httpCode === 200 ? 'Pesan test berhasil dikirim!' : 'Gagal mengirim pesan',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // Midtrans Settings
    public function midtrans()
    {
        $setting = IntegrationSetting::getByType('midtrans') ?? new IntegrationSetting(['type' => 'midtrans']);
        return view('admin.settings.midtrans', compact('setting'));
    }

    public function saveMidtrans(Request $request)
    {
        $request->validate([
            'server_key' => 'required|string',
            'client_key' => 'required|string',
        ]);

        IntegrationSetting::updateOrCreate(
            ['type' => 'midtrans'],
            [
                'name' => 'Midtrans Payment Gateway',
                'enabled' => $request->boolean('enabled'),
                'config' => [
                    'merchant_id' => $request->merchant_id,
                    'server_key' => $request->server_key,
                    'client_key' => $request->client_key,
                    'is_production' => $request->boolean('is_production'),
                ],
            ]
        );

        return back()->with('success', 'Konfigurasi Midtrans berhasil disimpan');
    }

    // Xendit Settings
    public function xendit()
    {
        $setting = IntegrationSetting::getByType('xendit') ?? new IntegrationSetting(['type' => 'xendit']);
        return view('admin.settings.xendit', compact('setting'));
    }

    public function saveXendit(Request $request)
    {
        $request->validate([
            'secret_key' => 'required|string',
        ]);

        IntegrationSetting::updateOrCreate(
            ['type' => 'xendit'],
            [
                'name' => 'Xendit Payment Gateway',
                'enabled' => $request->boolean('enabled'),
                'config' => [
                    'secret_key' => $request->secret_key,
                    'public_key' => $request->public_key,
                    'callback_token' => $request->callback_token,
                ],
            ]
        );

        return back()->with('success', 'Konfigurasi Xendit berhasil disimpan');
    }
}
