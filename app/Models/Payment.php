<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'razorpay_payment_id',
        'razorpay_order_id',
        'razorpay_signature',
        'status',
        'amount',
        'currency',
        'payload'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'payload' => 'array',
        'amount' => 'decimal:2',
    ];

    /**
     * Scope for successful payments
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success')
                    ->orWhere('status', 'captured');
    }

    /**
     * Scope for failed payments
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', 'created');
    }
}