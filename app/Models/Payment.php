<?php
// app/Models/Payment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bill_id',
        'wws_id',
        'amount_paid',
        'qr_number',
        'qr_date',
        'balance',
        'payment_method',
        'electronic_qr_number',
        'electronic_amount',
        'payment_gateway',
        'gateway_reference',
        'gateway_transaction_id',
        'gateway_response',
        'payment_status',
        'processed_at',
        'failure_reason',
        'status',
        'collector_name',
        'payment_date',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'electronic_amount' => 'decimal:2',
        'qr_date' => 'date',
        'payment_date' => 'datetime',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    // Accessors
    public function getFormattedAmountPaidAttribute()
    {
        return '₱' . number_format($this->amount_paid, 2);
    }

    public function getFormattedPaymentDateAttribute()
    {
        return $this->payment_date->format('M j, Y g:i A');
    }

    public function getPaymentMethodTextAttribute()
    {
        return str_replace('_', ' ', ucfirst($this->payment_method));
    }

    public function getPaymentGatewayTextAttribute()
    {
        return strtoupper($this->payment_gateway);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeOnline($query)
    {
        return $query->where('payment_method', 'online');
    }

    public function scopeOverTheCounter($query)
    {
        return $query->where('payment_method', 'over_the_counter');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, $months = 6)
    {
        return $query->where('payment_date', '>=', now()->subMonths($months));
    }

    public function scopeByGateway($query, $gateway)
    {
        return $query->where('payment_gateway', $gateway);
    }

    public function scopePendingStatus($query)
    {
        return $query->where('payment_status', 'pending');
    }

    public function scopeCompletedStatus($query)
    {
        return $query->where('payment_status', 'completed');
    }
}