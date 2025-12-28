<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'customer_id',
        'package_id',
        'amount',
        'tax_amount',
        'description',
        'status',
        'due_date',
        'paid_date',
        'paid_at',
        'invoice_number',
        'invoice_type',
        'payment_gateway',
        'payment_order_id',
        'transaction_id',
        'payment_method',
        'payment_reference',
        'payment_url',
        'collected_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'integer',
        'tax_amount' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function getTotalAmountAttribute()
    {
        return $this->amount + $this->tax_amount;
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function isOverdue()
    {
        return $this->status === 'unpaid' && $this->due_date && $this->due_date->isPast();
    }
}
