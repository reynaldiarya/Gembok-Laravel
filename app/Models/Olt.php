<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Olt extends Model
{
    protected $fillable = [
        'name', 'brand', 'model', 'ip_address', 'snmp_port', 'snmp_community',
        'snmp_version', 'telnet_username', 'telnet_password', 'telnet_port',
        'location', 'description', 'temperature', 'total_pon_ports',
        'total_onus', 'online_onus', 'offline_onus', 'los_onus', 'dyinggasp_onus',
        'uptime', 'status', 'last_sync'
    ];

    protected $casts = [
        'last_sync' => 'datetime',
        'temperature' => 'decimal:2',
    ];

    protected $hidden = ['telnet_password', 'snmp_community'];

    public function ponPorts()
    {
        return $this->hasMany(OltPonPort::class);
    }

    public function onus()
    {
        return $this->hasMany(Onu::class);
    }

    public function fans()
    {
        return $this->hasMany(OltFan::class);
    }

    public function getOnlinePercentageAttribute()
    {
        if ($this->total_onus == 0) return 0;
        return round(($this->online_onus / $this->total_onus) * 100, 2);
    }

    public function getLosPercentageAttribute()
    {
        if ($this->total_onus == 0) return 0;
        return round(($this->los_onus / $this->total_onus) * 100, 2);
    }
}
