<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'type',
        'name',
        'enabled',
        'config',
        'last_tested_at',
        'last_test_success',
        'last_test_message',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'array',
        'last_tested_at' => 'datetime',
        'last_test_success' => 'boolean',
    ];

    // Get setting by type
    public static function getByType(string $type): ?self
    {
        return self::where('type', $type)->first();
    }

    // Get config value
    public function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    // Set config value
    public function setConfig(string $key, $value): self
    {
        $config = $this->config ?? [];
        $config[$key] = $value;
        $this->config = $config;
        return $this;
    }

    // Check if integration is enabled and configured
    public function isActive(): bool
    {
        return $this->enabled && !empty($this->config);
    }

    // Update test result
    public function updateTestResult(bool $success, string $message = ''): self
    {
        $this->update([
            'last_tested_at' => now(),
            'last_test_success' => $success,
            'last_test_message' => $message,
        ]);
        return $this;
    }

    // Get Mikrotik settings
    public static function mikrotik(): ?self
    {
        return self::getByType('mikrotik');
    }

    // Get RADIUS settings
    public static function radius(): ?self
    {
        return self::getByType('radius');
    }

    // Get GenieACS settings
    public static function genieacs(): ?self
    {
        return self::getByType('genieacs');
    }

    // Get WhatsApp settings
    public static function whatsapp(): ?self
    {
        return self::getByType('whatsapp');
    }
}
