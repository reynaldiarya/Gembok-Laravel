<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OltPonPort extends Model
{
    protected $fillable = [
        'olt_id', 'port_name', 'slot', 'port', 'total_onus', 'online_onus',
        'rx_power', 'tx_power', 'status'
    ];

    protected $casts = [
        'rx_power' => 'decimal:2',
        'tx_power' => 'decimal:2',
    ];

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }

    public function onus()
    {
        return $this->hasMany(Onu::class, 'pon_port_id');
    }
}
