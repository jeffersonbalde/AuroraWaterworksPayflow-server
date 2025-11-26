<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'wws_id',
        'name',
        'email',
        'contact_number',
        'address',
        // Customer-specific fields
        'service',
        'connection_date',
        // Staff-specific fields
        'position',
        'created_by',
        'staff_notes',
        // Deactivation fields
        'deactivate_reason',
        'deactivated_at',
        'deactivated_by',
        // End staff-specific fields
        'avatar',
        'role',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'connection_date' => 'datetime',
        'password' => 'hashed',
    ];

    // Scope methods for different roles
    public function scopeClients($query)
    {
        return $query->where('role', 'client');
    }

    public function scopeStaff($query)
    {
        return $query->where('role', 'staff');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeDelinquent($query)
    {
        return $query->where('status', 'delinquent');
    }

    // Helper methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isDelinquent(): bool
    {
        return $this->status === 'delinquent';
    }

    // New helper methods for approval tracking
    public function isApproved(): bool
    {
        return $this->status === 'active' && !is_null($this->approved_at);
    }

    public function getApproverName(): ?string
    {
        return $this->approved_by;
    }

    public function getApprovalDate(): ?string
    {
        return $this->approved_at;
    }

    public function getRejectorName(): ?string
    {
        return $this->rejected_by;
    }

    public function getRejectionDate(): ?string
    {
        return $this->rejected_at;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejection_reason;
    }

    // New helper methods for deactivation
    public function isDeactivated(): bool
    {
        return $this->status === 'inactive' && !is_null($this->deactivated_at);
    }

    public function getDeactivatorName(): ?string
    {
        return $this->deactivated_by;
    }

    public function getDeactivationDate(): ?string
    {
        return $this->deactivated_at;
    }

    public function getDeactivationReason(): ?string
    {
        return $this->deactivate_reason;
    }

    // New helper methods for customer management
    public function getService(): ?string
    {
        return $this->service;
    }

    public function getConnectionDate(): ?string
    {
        return $this->connection_date;
    }

    // New helper methods for staff management
    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function getCreatedBy(): ?string
    {
        return $this->created_by;
    }

    public function getStaffNotes(): ?string
    {
        return $this->staff_notes;
    }

    // Check if user has staff-specific data
    public function hasStaffData(): bool
    {
        return !is_null($this->position) || !is_null($this->created_by) || !is_null($this->staff_notes);
    }

    // Check if user has customer-specific data
    public function hasCustomerData(): bool
    {
        return !is_null($this->service) || !is_null($this->connection_date);
    }

    // Get staff creation info for display
    public function getStaffCreationInfo(): array
    {
        return [
            'position' => $this->position,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'staff_notes' => $this->staff_notes,
        ];
    }

    // Get customer info for display
    public function getCustomerInfo(): array
    {
        return [
            'service' => $this->service,
            'connection_date' => $this->connection_date,
            'wws_id' => $this->wws_id,
        ];
    }

    // Get deactivation info for display
    public function getDeactivationInfo(): array
    {
        return [
            'deactivate_reason' => $this->deactivate_reason,
            'deactivated_by' => $this->deactivated_by,
            'deactivated_at' => $this->deactivated_at,
        ];
    }

    // Add this accessor to get the full avatar URL
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return null;
    }

    // Add this to include avatar_url in JSON responses
    protected $appends = ['avatar_url'];



    // In app/Models/User.php - Add these methods

    public function bills()
    {
        return $this->hasMany(Bill::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function pendingBills()
    {
        return $this->bills()->pending();
    }

    public function overdueBills()
    {
        return $this->bills()->overdue();
    }
}
