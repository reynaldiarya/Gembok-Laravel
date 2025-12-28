<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkDevice extends Model
{
    protected $fillable = [
        'name',
        'host',
        'community',
        'type',
        'location',
        'description',
        'is_active',
        'snmp_enabled',
        'status',
        'last_check',
        'uptime_seconds',
        'cpu_usage',
        'memory_usage',
        'temperature',
        'extra_data',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'snmp_enabled' => 'boolean',
        'last_check' => 'datetime',
        'cpu_usage' => 'float',
        'memory_usage' => 'float',
        'temperature' => 'float',
        'extra_data' => 'array',
    ];

    public function ipMonitors()
    {
        return $this->hasMany(IpMonitor::class);
    }

    public function alerts()
    {
        return $this->morphMany(NetworkAlert::class, 'alertable');
    }

    public function getUptimeFormattedAttribute()
    {
        if (!$this->uptime_seconds) return 'N/A';
        
        $days = floor($this->uptime_seconds / 86400);
        $hours = floor(($this->uptime_seconds % 86400) / 3600);
        $minutes = floor(($this->uptime_seconds % 3600) / 60);
        
        return "{$days}d {$hours}h {$minutes}m";
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'online' => 'green',
            'offline' => 'red',
            'warning' => 'yellow',
            default => 'gray',
        };
    }

    public function getTypeIconAttribute()
    {
        return match($this->type) {
            'router' => 'fa-server',
            'switch' => 'fa-network-wired',
            'olt' => 'fa-broadcast-tower',
            'server' => 'fa-database',
            'ap' => 'fa-wifi',
            default => 'fa-cube',
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline');
    }
}
