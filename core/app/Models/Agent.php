<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Agent extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'verified_at',
        'verified_by_admin_id',
        'documents',
        'profile_image',
        'address',
        'pan_number',
        'aadhaar_number',
        'total_bookings',
        'total_earnings',
        'pending_earnings',
        'created_by_admin_id',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'documents' => 'array',
        'total_earnings' => 'decimal:2',
        'pending_earnings' => 'decimal:2',
    ];

    // Relationships
    public function verifiedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'verified_by_admin_id');
    }

    public function createdByAdmin()
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function bookings()
    {
        return $this->hasMany(BookedTicket::class);
    }

    public function agentBookings()
    {
        return $this->hasMany(AgentBooking::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('verified_at');
    }

    // Accessors & Mutators
    public function getIsActiveAttribute()
    {
        return $this->status === 'active';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    public function getIsSuspendedAttribute()
    {
        return $this->status === 'suspended';
    }

    public function getIsVerifiedAttribute()
    {
        return !is_null($this->verified_at);
    }

    public function getProfileImageUrlAttribute()
    {
        if ($this->profile_image) {
            return asset('storage/' . $this->profile_image);
        }
        return asset('assets/images/default.png');
    }

    // Methods
    public function markAsVerified($adminId)
    {
        $this->update([
            'status' => 'active',
            'verified_at' => now(),
            'verified_by_admin_id' => $adminId,
        ]);
    }

    public function suspend()
    {
        $this->update(['status' => 'suspended']);
    }

    public function activate()
    {
        $this->update(['status' => 'active']);
    }

    public function updateLoginTime()
    {
        $this->update(['last_login_at' => now()]);
    }

    public function incrementBookings()
    {
        $this->increment('total_bookings');
    }

    public function addEarnings($amount)
    {
        $this->increment('total_earnings', $amount);
        $this->increment('pending_earnings', $amount);
    }

    public function markEarningsAsPaid($amount)
    {
        $this->decrement('pending_earnings', $amount);
    }

    public function getTotalCommissionEarned()
    {
        return $this->agentBookings()
            ->where('payment_status', 'paid')
            ->sum('total_commission_earned');
    }

    public function getPendingCommission()
    {
        return $this->agentBookings()
            ->where('payment_status', 'pending')
            ->sum('total_commission_earned');
    }
}