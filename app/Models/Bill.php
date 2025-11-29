<?php
// app/Models/Bill.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    /**
     * Automatic penalty rate for overdue bills (10%)
     */
    public const OVERDUE_PENALTY_RATE = 0.10;

    protected $fillable = [
        'user_id',
        'wws_id',
        'reading_date',
        'due_date',
        'previous_reading',
        'present_reading',
        'consumption',
        'amount',
        'penalty',
        'total_payable',
        'restated_amount',
        'restatement_reason',
        'authorization_code',
        'status',
        'meter_reader',
        'online_meter_used',
        'paid_at',
        'qr_number',
    ];

    protected $casts = [
        'reading_date' => 'date',
        'due_date' => 'date',
        'previous_reading' => 'decimal:2',
        'present_reading' => 'decimal:2',
        'consumption' => 'decimal:2',
        'amount' => 'decimal:2',
        'penalty' => 'decimal:2',
        'total_payable' => 'decimal:2',
        'restated_amount' => 'decimal:2',
        'online_meter_used' => 'boolean',
        'paid_at' => 'datetime',
    ];

    // Add this boot method to calculate consumption and total automatically
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bill) {
            $bill->calculateFields();
        });

        static::updating(function ($bill) {
            $bill->calculateFields();
        });
    }

    protected function calculateFields()
    {
        // Calculate consumption if readings are provided but consumption is not set
        if ($this->present_reading > 0 && $this->previous_reading >= 0 && is_null($this->consumption)) {
            $this->consumption = $this->present_reading - $this->previous_reading;
        }

        // Keep stored total payable in sync when amount/penalty are explicitly set.
        // Note: automatic overdue penalties are calculated dynamically and are not
        // persisted to this column.
        if ($this->amount > 0 && is_null($this->total_payable)) {
            $this->total_payable = $this->amount + $this->penalty;
        }
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->due_date < now() && $this->status === 'pending';
    }

    public function getDaysOverdueAttribute()
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return now()->diffInDays($this->due_date);
    }

    public function getFormattedAmountAttribute()
    {
        return '₱' . number_format($this->amount, 2);
    }

    public function getFormattedTotalPayableAttribute()
    {
        return '₱' . number_format($this->total_payable, 2);
    }

    /**
     * Get the automatically calculated penalty for overdue bills.
     *
     * This does NOT use the stored "penalty" column. Instead, it applies
     * a fixed 10% rate to the base amount when the bill is overdue.
     */
    public function getAutomaticPenaltyAttribute(): float
    {
        if (!$this->is_overdue || $this->amount <= 0) {
            return 0.0;
        }

        return round((float) $this->amount * static::OVERDUE_PENALTY_RATE, 2);
    }

    /**
     * Get the total payable using the automatic penalty rule.
     */
    public function getAutomaticTotalPayableAttribute(): float
    {
        return round((float) $this->amount + $this->automatic_penalty, 2);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->where('status', 'pending');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCurrent($query)
    {
        return $query->where('reading_date', '>=', now()->subMonths(6));
    }
}