<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OltFan extends Model
{
    protected $fillable = [
        'olt_id', 'fan_name', 'fan_index', 'speed_rpm', 'speed_level', 'status'
    ];

    public function olt()
    {
        return $this->belongsTo(Olt::class);
    }
}
