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
        // Staff-specific fields
        'position',
        'created_by', 
        'staff_notes',
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
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
}