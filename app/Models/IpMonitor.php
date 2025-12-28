<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpMonitor extends Model
{
    protected $fillable = [
        'ip_address',
        'name',
        'customer_id',
        'network_device_id',
        'status',
        'latency_ms',
        'packet_loss',
        'last_check',
        'last_up',
        'last_down',
        'check_interval',
        'alert_threshold',
        'consecutive_failures',
        'is_active',
        'alert_enabled',
    ];

    protected $casts = [
        'last_check' => 'datetime',
        'last_up' => 'datetime',
        'last_down' => 'datetime',
        'is_active' => 'boolean',
        'alert_enabled' => 'boolean',
        'packet_loss' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function networkDevice()
    {
        return $this->belongsTo(NetworkDevice::class);
    }

    public function logs()
    {
        return $this->hasMany(IpMonitorLog::class);
    }

    public function alerts()
    {
        return $this->morphMany(NetworkAlert::class, 'alertable');
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'up' => 'green',
            'down' => 'red',
            default => 'gray',
        };
    }

    public function getDisplayNameAttribute()
    {
        if ($this->name) return $this->name;
        if ($this->customer) return $this->customer->name;
        return $this->ip_address;
    }

    public function getUptimePercentAttribute()
    {
        $totalLogs = $this->logs()->where('checked_at', '>=', now()->subDays(30))->count();
        if ($totalLogs === 0) return 100;
        
        $upLogs = $this->logs()->where('checked_at', '>=', now()->subDays(30))->where('status', 'up')->count();
        return round(($upLogs / $totalLogs) * 100, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeUp($query)
    {
        return $query->where('status', 'up');
    }

    public function scopeDown($query)
    {
        return $query->where('status', 'down');
    }
}
