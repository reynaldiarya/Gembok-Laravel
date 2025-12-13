<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\IntegrationSetting;

class RadiusService
{
    protected $connection;
    protected $enabled;
    protected $nasSecret;

    public function __construct()
    {
        // Try to get config from database first
        $setting = IntegrationSetting::radius();
        
        if ($setting && $setting->isActive()) {
            $host = $setting->getConfig('host');
            $port = $setting->getConfig('port', 3306);
            $database = $setting->getConfig('database', 'radius');
            $username = $setting->getConfig('username');
            $password = $setting->getConfig('password');
            $this->nasSecret = $setting->getConfig('nas_secret', 'testing123');
            $this->enabled = true;
        } else {
            // Fallback to config file
            $host = config('services.radius.host', '127.0.0.1');
            $port = config('services.radius.port', 3306);
            $database = config('services.radius.database', 'radius');
            $username = config('services.radius.username', 'radius');
            $password = config('services.radius.password', '');
            $this->nasSecret = config('services.radius.nas_secret', 'testing123');
            $this->enabled = config('services.radius.enabled', false);
        }
        
        if ($this->enabled) {
            config(['database.connections.radius' => [
                'driver' => 'mysql',
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ]]);
            
            $this->connection = 'radius';
        }
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Create RADIUS user (radcheck table)
     */
    public function createUser($username, $password, $attributes = [])
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            // Insert password
            DB::connection($this->connection)->table('radcheck')->insert([
                'username' => $username,
                'attribute' => 'Cleartext-Password',
                'op' => ':=',
                'value' => $password,
            ]);

            // Insert additional attributes
            foreach ($attributes as $attr => $value) {
                DB::connection($this->connection)->table('radcheck')->insert([
                    'username' => $username,
                    'attribute' => $attr,
                    'op' => ':=',
                    'value' => $value,
                ]);
            }

            Log::info('RADIUS user created', ['username' => $username]);
            return true;
        } catch (\Exception $e) {
            Log::error('RADIUS create user failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update RADIUS user password
     */
    public function updatePassword($username, $password)
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            DB::connection($this->connection)->table('radcheck')
                ->where('username', $username)
                ->where('attribute', 'Cleartext-Password')
                ->update(['value' => $password]);

            Log::info('RADIUS password updated', ['username' => $username]);
            return true;
        } catch (\Exception $e) {
            Log::error('RADIUS update password failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete RADIUS user
     */
    public function deleteUser($username)
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            DB::connection($this->connection)->table('radcheck')
                ->where('username', $username)->delete();
            DB::connection($this->connection)->table('radreply')
                ->where('username', $username)->delete();
            DB::connection($this->connection)->table('radusergroup')
                ->where('username', $username)->delete();

            Log::info('RADIUS user deleted', ['username' => $username]);
            return true;
        } catch (\Exception $e) {
            Log::error('RADIUS delete user failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Assign user to group (for bandwidth profile)
     */
    public function assignGroup($username, $groupname, $priority = 1)
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            DB::connection($this->connection)->table('radusergroup')->updateOrInsert(
                ['username' => $username],
                ['groupname' => $groupname, 'priority' => $priority]
            );

            Log::info('RADIUS group assigned', ['username' => $username, 'group' => $groupname]);
            return true;
        } catch (\Exception $e) {
            Log::error('RADIUS assign group failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create bandwidth group/profile
     */
    public function createGroup($groupname, $downloadLimit, $uploadLimit)
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            // Mikrotik-Rate-Limit format: rx/tx (download/upload)
            $rateLimit = "{$uploadLimit}/{$downloadLimit}";
            
            DB::connection($this->connection)->table('radgroupreply')->updateOrInsert(
                ['groupname' => $groupname, 'attribute' => 'Mikrotik-Rate-Limit'],
                ['op' => ':=', 'value' => $rateLimit]
            );

            Log::info('RADIUS group created', ['group' => $groupname, 'rate' => $rateLimit]);
            return true;
        } catch (\Exception $e) {
            Log::error('RADIUS create group failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get online users from radacct
     */
    public function getOnlineUsers()
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            return DB::connection($this->connection)->table('radacct')
                ->whereNull('acctstoptime')
                ->select([
                    'username',
                    'nasipaddress',
                    'framedipaddress',
                    'acctstarttime',
                    'acctinputoctets',
                    'acctoutputoctets',
                    'callingstationid',
                ])
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('RADIUS get online users failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Disconnect user (CoA - Change of Authorization)
     */
    public function disconnectUser($username)
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            // Get active session
            $session = DB::connection($this->connection)->table('radacct')
                ->where('username', $username)
                ->whereNull('acctstoptime')
                ->first();

            if (!$session) {
                return false;
            }

            // Send CoA disconnect via radclient (requires shell access)
            $nasIp = $session->nasipaddress;
            $secret = $this->nasSecret;
            $sessionId = $session->acctsessionid;

            $command = "echo 'User-Name={$username},Acct-Session-Id={$sessionId}' | radclient -x {$nasIp}:3799 disconnect {$secret}";
            
            exec($command, $output, $returnCode);

            Log::info('RADIUS disconnect attempt', [
                'username' => $username,
                'return_code' => $returnCode
            ]);

            return $returnCode === 0;
        } catch (\Exception $e) {
            Log::error('RADIUS disconnect failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user accounting history
     */
    public function getUserHistory($username, $limit = 30)
    {
        if (!$this->enabled) {
            return [];
        }

        try {
            return DB::connection($this->connection)->table('radacct')
                ->where('username', $username)
                ->orderBy('acctstarttime', 'desc')
                ->limit($limit)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::error('RADIUS get history failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Suspend user (disable login)
     */
    public function suspendUser($username)
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            // Add Auth-Type := Reject
            DB::connection($this->connection)->table('radcheck')->updateOrInsert(
                ['username' => $username, 'attribute' => 'Auth-Type'],
                ['op' => ':=', 'value' => 'Reject']
            );

            // Disconnect active session
            $this->disconnectUser($username);

            Log::info('RADIUS user suspended', ['username' => $username]);
            return true;
        } catch (\Exception $e) {
            Log::error('RADIUS suspend failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Unsuspend user (enable login)
     */
    public function unsuspendUser($username)
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            DB::connection($this->connection)->table('radcheck')
                ->where('username', $username)
                ->where('attribute', 'Auth-Type')
                ->delete();

            Log::info('RADIUS user unsuspended', ['username' => $username]);
            return true;
        } catch (\Exception $e) {
            Log::error('RADIUS unsuspend failed: ' . $e->getMessage());
            return false;
        }
    }
}
