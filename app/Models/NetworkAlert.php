<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NetworkAlert extends Model
{
    protected $fillable = [
        'alertable_type',
        'alertable_id',
        'type',
        'title',
        'message',
        'is_read',
        'notification_sent',
        'resolved_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'notification_sent' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function alertable()
    {
        return $this->morphTo();
    }

    public function getTypeColorAttribute()
    {
        return match($this->type) {
            'down', 'critical' => 'red',
            'warning' => 'yellow',
            'up', 'recovery' => 'green',
            default => 'gray',
        };
    }

    public function getTypeIconAttribute()
    {
        return match($this->type) {
            'down', 'critical' => 'fa-times-circle',
            'warning' => 'fa-exclamation-triangle',
            'up', 'recovery' => 'fa-check-circle',
            default => 'fa-info-circle',
        };
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }
}
