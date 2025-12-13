<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\IntegrationSetting;

class GenieAcsService
{
    protected $baseUrl;
    protected $username;
    protected $password;

    public function __construct()
    {
        // Try to get config from database first
        $setting = IntegrationSetting::genieacs();
        
        if ($setting && $setting->isActive()) {
            $this->baseUrl = $setting->getConfig('url');
            $this->username = $setting->getConfig('username');
            $this->password = $setting->getConfig('password');
        } else {
            // Fallback to config file
            $this->baseUrl = config('services.genieacs.url');
            $this->username = config('services.genieacs.username');
            $this->password = config('services.genieacs.password');
        }
    }

    protected function request($method, $endpoint, $data = [])
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->$method($this->baseUrl . $endpoint, $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('GenieACS request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('GenieACS request exception: ' . $e->getMessage());
            return null;
        }
    }

    // ==================== Device Management ====================

    public function getDevices($query = [])
    {
        $queryString = !empty($query) ? '?' . http_build_query(['query' => json_encode($query)]) : '';
        return $this->request('get', '/devices' . $queryString);
    }

    public function getDevice($deviceId)
    {
        // GenieACS NBI requires query filter to get single device
        $query = ['_id' => $deviceId];
        $queryString = '?' . http_build_query(['query' => json_encode($query)]);
        $devices = $this->request('get', '/devices' . $queryString);
        
        // Return first device or null
        return $devices && count($devices) > 0 ? $devices[0] : null;
    }

    public function refreshDevice($deviceId)
    {
        $data = [
            'name' => 'refreshObject',
            'objectName' => ''
        ];
        
        return $this->request('post', '/devices/' . urlencode($deviceId) . '/tasks', $data);
    }

    public function getDeviceParameters($deviceId, $parameters = [])
    {
        $device = $this->getDevice($deviceId);
        
        if (!$device) {
            return null;
        }

        $result = [];
        foreach ($parameters as $param) {
            $result[$param] = $this->getParameterValue($device, $param);
        }

        return $result;
    }

    protected function getParameterValue($device, $path)
    {
        $keys = explode('.', $path);
        $value = $device;

        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        // Extract _value if exists
        if (is_array($value)) {
            if (isset($value['_value'])) {
                $value = $value['_value'];
            } else {
                return null;
            }
        }

        // Ensure we return a scalar value or null
        return is_scalar($value) ? $value : null;
    }

    // ==================== Remote Control ====================

    public function rebootDevice($deviceId)
    {
        $data = [
            'name' => 'reboot'
        ];
        
        Log::info('Rebooting device', ['device_id' => $deviceId]);
        return $this->request('post', '/devices/' . urlencode($deviceId) . '/tasks', $data);
    }

    public function factoryReset($deviceId)
    {
        $data = [
            'name' => 'factoryReset'
        ];
        
        Log::info('Factory reset device', ['device_id' => $deviceId]);
        return $this->request('post', '/devices/' . urlencode($deviceId) . '/tasks', $data);
    }

    public function setParameterValues($deviceId, $parameters)
    {
        $data = [
            'name' => 'setParameterValues',
            'parameterValues' => $parameters
        ];
        
        return $this->request('post', '/devices/' . urlencode($deviceId) . '/tasks', $data);
    }

    public function updateWiFiSettings($deviceId, $settings)
    {
        $parameters = [];

        if (isset($settings['ssid'])) {
            $parameters[] = [
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
                $settings['ssid'],
                'xsd:string'
            ];
        }

        if (isset($settings['password'])) {
            $parameters[] = [
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase',
                $settings['password'],
                'xsd:string'
            ];
        }

        if (isset($settings['channel'])) {
            $parameters[] = [
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Channel',
                $settings['channel'],
                'xsd:unsignedInt'
            ];
        }

        if (isset($settings['enabled'])) {
            $parameters[] = [
                'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Enable',
                $settings['enabled'] ? '1' : '0',
                'xsd:boolean'
            ];
        }

        return $this->setParameterValues($deviceId, $parameters);
    }

    // ==================== Provisioning ====================

    public function getPresets()
    {
        return $this->request('get', '/presets');
    }

    public function createPreset($data)
    {
        return $this->request('post', '/presets', $data);
    }

    public function deletePreset($presetName)
    {
        return $this->request('delete', '/presets/' . urlencode($presetName));
    }

    // ==================== Monitoring ====================

    public function getDeviceStatus($deviceId)
    {
        $device = $this->getDevice($deviceId);
        
        if (!$device) {
            return null;
        }

        $lastInform = $device['_lastInform'] ?? null;
        
        // Handle different lastInform formats (timestamp or ISO date string)
        $lastInformTimestamp = null;
        if ($lastInform) {
            if (is_numeric($lastInform)) {
                $lastInformTimestamp = (int) $lastInform;
            } elseif (is_string($lastInform)) {
                $lastInformTimestamp = strtotime($lastInform) * 1000;
            }
        }
        
        $now = time() * 1000; // Convert to milliseconds
        $isOnline = $lastInformTimestamp && ($now - $lastInformTimestamp) < 300000; // 5 minutes

        return [
            'device_id' => $deviceId,
            'status' => $isOnline ? 'online' : 'offline',
            'model' => $this->getParameterValue($device, 'InternetGatewayDevice.DeviceInfo.ModelName'),
            'manufacturer' => $this->getParameterValue($device, 'InternetGatewayDevice.DeviceInfo.Manufacturer'),
            'firmware' => $this->getParameterValue($device, 'InternetGatewayDevice.DeviceInfo.SoftwareVersion'),
            'serial_number' => $this->getParameterValue($device, 'InternetGatewayDevice.DeviceInfo.SerialNumber'),
            'mac_address' => $this->getParameterValue($device, 'InternetGatewayDevice.LANDevice.1.LANEthernetInterfaceConfig.1.MACAddress'),
            'ip_address' => $this->getParameterValue($device, 'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANIPConnection.1.ExternalIPAddress'),
            'uptime' => $this->getParameterValue($device, 'InternetGatewayDevice.DeviceInfo.UpTime'),
            'last_inform' => $lastInformTimestamp ? date('Y-m-d H:i:s', $lastInformTimestamp / 1000) : null,
        ];
    }

    public function getWiFiInfo($deviceId)
    {
        $device = $this->getDevice($deviceId);
        
        if (!$device) {
            return null;
        }

        return [
            'ssid' => $this->getParameterValue($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID'),
            'enabled' => $this->getParameterValue($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Enable'),
            'channel' => $this->getParameterValue($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Channel'),
            'encryption' => $this->getParameterValue($device, 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.BeaconType'),
        ];
    }

    // ==================== Diagnostic Tools ====================

    public function ping($deviceId, $host, $count = 4)
    {
        $data = [
            'name' => 'download',
            'fileType' => 'IPPingDiagnostics',
            'fileName' => json_encode([
                'Host' => $host,
                'NumberOfRepetitions' => $count
            ])
        ];
        
        return $this->request('post', '/devices/' . urlencode($deviceId) . '/tasks', $data);
    }

    public function traceroute($deviceId, $host)
    {
        $data = [
            'name' => 'download',
            'fileType' => 'TraceRoute',
            'fileName' => json_encode([
                'Host' => $host
            ])
        ];
        
        return $this->request('post', '/devices/' . urlencode($deviceId) . '/tasks', $data);
    }

    // ==================== Firmware Management ====================

    public function upgradeFirmware($deviceId, $firmwareUrl)
    {
        $data = [
            'name' => 'download',
            'fileType' => '1 Firmware Upgrade Image',
            'fileName' => $firmwareUrl
        ];
        
        Log::info('Upgrading firmware', ['device_id' => $deviceId, 'url' => $firmwareUrl]);
        return $this->request('post', '/devices/' . urlencode($deviceId) . '/tasks', $data);
    }

    // ==================== Bulk Operations ====================

    public function bulkRefresh($deviceIds)
    {
        $results = [];
        foreach ($deviceIds as $deviceId) {
            $results[$deviceId] = $this->refreshDevice($deviceId);
        }
        return $results;
    }

    public function bulkReboot($deviceIds)
    {
        $results = [];
        foreach ($deviceIds as $deviceId) {
            $results[$deviceId] = $this->rebootDevice($deviceId);
        }
        return $results;
    }
}
