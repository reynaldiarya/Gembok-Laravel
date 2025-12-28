<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpMonitorLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'ip_monitor_id',
        'status',
        'latency_ms',
        'packet_loss',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'packet_loss' => 'float',
    ];

    public function ipMonitor()
    {
        return $this->belongsTo(IpMonitor::class);
    }

    public function getStatusColorAttribute()
    {
        return $this->status === 'up' ? 'green' : 'red';
    }
}
