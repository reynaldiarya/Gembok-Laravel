<?php

namespace App\Services;

use RouterOS\Client;
use RouterOS\Query;
use Illuminate\Support\Facades\Log;
use App\Models\IntegrationSetting;

class MikrotikService
{
    protected $client;
    protected $connected = false;

    public function __construct(array $config = null)
    {
        try {
            // If config provided directly (for testing), use it
            if ($config) {
                $host = $config['host'] ?? null;
                $username = $config['username'] ?? null;
                $password = $config['password'] ?? null;
                $port = (int) ($config['port'] ?? 8728);
                $enabled = true;
            } else {
                // Try to get config from database first
                $setting = IntegrationSetting::mikrotik();
                
                if ($setting && $setting->isActive()) {
                    $host = $setting->getConfig('host');
                    $username = $setting->getConfig('username');
                    $password = $setting->getConfig('password');
                    $port = (int) $setting->getConfig('port', 8728);
                    $enabled = true;
                } else {
                    // Fallback to config file
                    $host = config('services.mikrotik.host');
                    $username = config('services.mikrotik.username');
                    $password = config('services.mikrotik.password');
                    $port = (int) config('services.mikrotik.port', 8728);
                    $enabled = config('services.mikrotik.enabled', false);
                }
            }
            
            // Skip connection if not enabled or host not configured
            if (!$enabled || empty($host)) {
                $this->connected = false;
                return;
            }
            
            $this->client = new Client([
                'host' => $host,
                'user' => $username,
                'pass' => $password,
                'port' => $port,
            ]);
            $this->connected = true;
        } catch (\Exception $e) {
            Log::error('Mikrotik connection failed: ' . $e->getMessage());
            $this->connected = false;
        }
    }
    
    public function connect()
    {
        return $this->connected;
    }
    
    public function getSystemIdentity()
    {
        if (!$this->connected) {
            return null;
        }

        try {
            $query = new Query('/system/identity/print');
            $result = $this->client->query($query)->read();
            return $result[0]['name'] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to get system identity: ' . $e->getMessage());
            return null;
        }
    }

    public function isConnected()
    {
        return $this->connected;
    }

    // ==================== PPPoE Management ====================

    public function createPPPoESecret($data)
    {
        if (!$this->connected) {
            return false;
        }

        try {
            $query = new Query('/ppp/secret/add');
            $query->equal('name', $data['username']);
            $query->equal('password', $data['password']);
            $query->equal('service', 'pppoe');
            $query->equal('profile', $data['profile'] ?? 'default');
            
            if (isset($data['local_address'])) {
                $query->equal('local-address', $data['local_address']);
            }
            
            if (isset($data['remote_address'])) {
                $query->equal('remote-address', $data['remote_address']);
            }
            
            $query->equal('comment', $data['comment'] ?? '');

            $response = $this->client->query($query)->read();
            
            Log::info('PPPoE Secret created', ['username' => $data['username']]);
            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to create PPPoE secret: ' . $e->getMessage());
            return false;
        }
    }

    public function updatePPPoESecret($username, $data)
    {
        if (!$this->connected) {
            return false;
        }

        try {
            // Find secret ID
            $query = new Query('/ppp/secret/print');
            $query->where('name', $username);
            $secrets = $this->client->query($query)->read();

            if (empty($secrets)) {
                return false;
            }

            $secretId = $secrets[0]['.id'];

            // Update secret
            $query = new Query('/ppp/secret/set');
            $query->equal('.id', $secretId);
            
            if (isset($data['password'])) {
                $query->equal('password', $data['password']);
            }
            
            if (isset($data['profile'])) {
                $query->equal('profile', $data['profile']);
            }
            
            if (isset($data['comment'])) {
                $query->equal('comment', $data['comment']);
            }

            $this->client->query($query)->read();
            
            Log::info('PPPoE Secret updated', ['username' => $username]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update PPPoE secret: ' . $e->getMessage());
            return false;
        }
    }

    public function deletePPPoESecret($username)
    {
        if (!$this->connected) {
            return false;
        }

        try {
            $query = new Query('/ppp/secret/print');
            $query->where('name', $username);
            $secrets = $this->client->query($query)->read();

            if (empty($secrets)) {
                return false;
            }

            $secretId = $secrets[0]['.id'];

            $query = new Query('/ppp/secret/remove');
            $query->equal('.id', $secretId);
            $this->client->query($query)->read();
            
            Log::info('PPPoE Secret deleted', ['username' => $username]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete PPPoE secret: ' . $e->getMessage());
            return false;
        }
    }

    public function getPPPoEActive()
    {
        if (!$this->connected) {
            return [];
        }

        try {
            $query = new Query('/ppp/active/print');
            $active = $this->client->query($query)->read();
            
            return collect($active)->map(function ($session) {
                return [
                    'id' => $session['.id'] ?? null,
                    'name' => $session['name'] ?? null,
                    'address' => $session['address'] ?? null,
                    'uptime' => $session['uptime'] ?? null,
                    'caller_id' => $session['caller-id'] ?? null,
                    'service' => $session['service'] ?? null,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get PPPoE active: ' . $e->getMessage());
            return [];
        }
    }

    public function disconnectPPPoE($username)
    {
        if (!$this->connected) {
            return false;
        }

        try {
            $query = new Query('/ppp/active/print');
            $query->where('name', $username);
            $active = $this->client->query($query)->read();

            if (empty($active)) {
                return false;
            }

            $sessionId = $active[0]['.id'];

            $query = new Query('/ppp/active/remove');
            $query->equal('.id', $sessionId);
            $this->client->query($query)->read();
            
            Log::info('PPPoE disconnected', ['username' => $username]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disconnect PPPoE: ' . $e->getMessage());
            return false;
        }
    }

    // ==================== Profile Management ====================

    public function createPPPoEProfile($data)
    {
        if (!$this->connected) {
            return false;
        }

        try {
            $query = new Query('/ppp/profile/add');
            $query->equal('name', $data['name']);
            $query->equal('local-address', $data['local_address'] ?? 'pool-pppoe');
            $query->equal('remote-address', $data['remote_address'] ?? 'pool-pppoe');
            
            if (isset($data['rate_limit'])) {
                $query->equal('rate-limit', $data['rate_limit']);
            }

            $this->client->query($query)->read();
            
            Log::info('PPPoE Profile created', ['name' => $data['name']]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create PPPoE profile: ' . $e->getMessage());
            return false;
        }
    }

    // ==================== Hotspot Management ====================

    public function createHotspotUser($data)
    {
        if (!$this->connected) {
            return false;
        }

        try {
            $query = new Query('/ip/hotspot/user/add');
            $query->equal('name', $data['username']);
            $query->equal('password', $data['password']);
            $query->equal('profile', $data['profile'] ?? 'default');
            
            if (isset($data['limit_uptime'])) {
                $query->equal('limit-uptime', $data['limit_uptime']);
            }
            
            if (isset($data['limit_bytes_total'])) {
                $query->equal('limit-bytes-total', $data['limit_bytes_total']);
            }
            
            $query->equal('comment', $data['comment'] ?? '');

            $this->client->query($query)->read();
            
            Log::info('Hotspot user created', ['username' => $data['username']]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create hotspot user: ' . $e->getMessage());
            return false;
        }
    }

    public function getHotspotActive()
    {
        if (!$this->connected) {
            return [];
        }

        try {
            $query = new Query('/ip/hotspot/active/print');
            $active = $this->client->query($query)->read();
            
            return collect($active)->map(function ($session) {
                return [
                    'id' => $session['.id'] ?? null,
                    'user' => $session['user'] ?? null,
                    'address' => $session['address'] ?? null,
                    'mac_address' => $session['mac-address'] ?? null,
                    'uptime' => $session['uptime'] ?? null,
                    'bytes_in' => $session['bytes-in'] ?? 0,
                    'bytes_out' => $session['bytes-out'] ?? 0,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get hotspot active: ' . $e->getMessage());
            return [];
        }
    }

    // ==================== Monitoring ====================

    public function getSystemResource()
    {
        if (!$this->connected) {
            return null;
        }

        try {
            $query = new Query('/system/resource/print');
            $resource = $this->client->query($query)->read();
            
            return $resource[0] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to get system resource: ' . $e->getMessage());
            return null;
        }
    }

    public function getInterfaces()
    {
        if (!$this->connected) {
            return [];
        }

        try {
            $query = new Query('/interface/print');
            return $this->client->query($query)->read();
        } catch (\Exception $e) {
            Log::error('Failed to get interfaces: ' . $e->getMessage());
            return [];
        }
    }

    public function getTrafficStats($interface = 'ether1')
    {
        if (!$this->connected) {
            return null;
        }

        try {
            $query = new Query('/interface/print');
            $query->where('name', $interface);
            $result = $this->client->query($query)->read();
            
            if (empty($result)) {
                return null;
            }

            return [
                'rx_bytes' => $result[0]['rx-byte'] ?? 0,
                'tx_bytes' => $result[0]['tx-byte'] ?? 0,
                'rx_packets' => $result[0]['rx-packet'] ?? 0,
                'tx_packets' => $result[0]['tx-packet'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get traffic stats: ' . $e->getMessage());
            return null;
        }
    }

    // ==================== Sync Methods ====================

    /**
     * Get all PPPoE Secrets from Mikrotik
     */
    public function getPPPoESecrets()
    {
        if (!$this->connected) {
            return [];
        }

        try {
            $query = new Query('/ppp/secret/print');
            $secrets = $this->client->query($query)->read();
            
            return collect($secrets)->map(function ($secret) {
                return [
                    'id' => $secret['.id'] ?? null,
                    'name' => $secret['name'] ?? null,
                    'password' => $secret['password'] ?? null,
                    'service' => $secret['service'] ?? 'pppoe',
                    'profile' => $secret['profile'] ?? 'default',
                    'local_address' => $secret['local-address'] ?? null,
                    'remote_address' => $secret['remote-address'] ?? null,
                    'comment' => $secret['comment'] ?? null,
                    'disabled' => ($secret['disabled'] ?? 'false') === 'true',
                    'caller_id' => $secret['caller-id'] ?? null,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get PPPoE secrets: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all PPPoE Profiles from Mikrotik
     */
    public function getPPPoEProfiles()
    {
        if (!$this->connected) {
            return [];
        }

        try {
            $query = new Query('/ppp/profile/print');
            $profiles = $this->client->query($query)->read();
            
            return collect($profiles)->map(function ($profile) {
                return [
                    'id' => $profile['.id'] ?? null,
                    'name' => $profile['name'] ?? null,
                    'local_address' => $profile['local-address'] ?? null,
                    'remote_address' => $profile['remote-address'] ?? null,
                    'rate_limit' => $profile['rate-limit'] ?? null,
                    'parent_queue' => $profile['parent-queue'] ?? null,
                    'address_list' => $profile['address-list'] ?? null,
                    'dns_server' => $profile['dns-server'] ?? null,
                    'only_one' => $profile['only-one'] ?? 'default',
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get PPPoE profiles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all Hotspot Users from Mikrotik
     */
    public function getHotspotUsers()
    {
        if (!$this->connected) {
            return [];
        }

        try {
            $query = new Query('/ip/hotspot/user/print');
            $users = $this->client->query($query)->read();
            
            return collect($users)->map(function ($user) {
                return [
                    'id' => $user['.id'] ?? null,
                    'name' => $user['name'] ?? null,
                    'password' => $user['password'] ?? null,
                    'profile' => $user['profile'] ?? 'default',
                    'limit_uptime' => $user['limit-uptime'] ?? null,
                    'limit_bytes_total' => $user['limit-bytes-total'] ?? null,
                    'limit_bytes_in' => $user['limit-bytes-in'] ?? null,
                    'limit_bytes_out' => $user['limit-bytes-out'] ?? null,
                    'comment' => $user['comment'] ?? null,
                    'disabled' => ($user['disabled'] ?? 'false') === 'true',
                    'mac_address' => $user['mac-address'] ?? null,
                    'server' => $user['server'] ?? 'all',
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get Hotspot users: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all Hotspot Profiles from Mikrotik
     */
    public function getHotspotProfiles()
    {
        if (!$this->connected) {
            return [];
        }

        try {
            $query = new Query('/ip/hotspot/user/profile/print');
            $profiles = $this->client->query($query)->read();
            
            return collect($profiles)->map(function ($profile) {
                return [
                    'id' => $profile['.id'] ?? null,
                    'name' => $profile['name'] ?? null,
                    'rate_limit' => $profile['rate-limit'] ?? null,
                    'shared_users' => $profile['shared-users'] ?? 1,
                    'session_timeout' => $profile['session-timeout'] ?? null,
                    'idle_timeout' => $profile['idle-timeout'] ?? null,
                    'keepalive_timeout' => $profile['keepalive-timeout'] ?? null,
                    'address_pool' => $profile['address-pool'] ?? null,
                ];
            })->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get Hotspot profiles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse rate limit string to get speed in Mbps
     * Format: "10M/10M" or "10M" or "10240k/10240k"
     */
    public function parseRateLimit($rateLimit)
    {
        if (empty($rateLimit)) {
            return ['upload' => 0, 'download' => 0];
        }

        $parts = explode('/', $rateLimit);
        $upload = $this->parseSpeed($parts[0] ?? '0');
        $download = $this->parseSpeed($parts[1] ?? $parts[0] ?? '0');

        return ['upload' => $upload, 'download' => $download];
    }

    /**
     * Parse speed string to Mbps
     */
    private function parseSpeed($speed)
    {
        $speed = strtolower(trim($speed));
        
        if (strpos($speed, 'g') !== false) {
            return (float) str_replace(['g', 'G'], '', $speed) * 1000;
        } elseif (strpos($speed, 'm') !== false) {
            return (float) str_replace(['m', 'M'], '', $speed);
        } elseif (strpos($speed, 'k') !== false) {
            return (float) str_replace(['k', 'K'], '', $speed) / 1024;
        }
        
        return (float) $speed / 1048576; // bytes to Mbps
    }
}
