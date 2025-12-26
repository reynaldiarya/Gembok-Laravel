<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnuStatusLog extends Model
{
    protected $fillable = ['onu_id', 'old_status', 'new_status', 'reason'];

    public function onu()
    {
        return $this->belongsTo(Onu::class);
    }
}
