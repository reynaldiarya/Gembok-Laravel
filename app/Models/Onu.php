<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Onu extends Model
{
    protected $fillable = [
        'olt_id', 'pon_port_id', 'customer_id', 'serial_number', 'mac_address',
        'name', 'model', 'pon_location', 'onu_id', 'rx_power', 'tx_power',
        'temperature', 'voltage', 'bias_current', 'rx_bytes', 'tx_bytes',
        'firmware_version', 'hardware_version', 'ip_address', 'status',
        'last_online', 'last_offline', 'offline_reason'
    ];

    protected $casts = [
        'last_online' => 'datetime',
        'last_offline' => 'datetime',
        'rx_power' => 'decimal:2',
        'tx_power' => 'decimal:2',
        'temperature' => 'decimal:2',
        'voltage' => 'decimal:2',
    ];

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function ponPort()
    {
        return $this->belongsTo(OltPonPort::class, 'pon_port_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(OnuStatusLog::class);
    }

    public function getRxPowerStatusAttribute()
    {
        if ($this->rx_power === null) return 'unknown';
        if ($this->rx_power >= -25) return 'good';
        if ($this->rx_power >= -28) return 'warning';
        return 'critical';
    }
}
