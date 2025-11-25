<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_event_id',
        'beneficiary_user_id',
        'reward_type',
        'basis_amount',
        'amount_awarded',
        'status',
        'reason',
        'credited_at'
    ];

    protected $casts = [
        'basis_amount' => 'decimal:2',
        'amount_awarded' => 'decimal:2',
        'credited_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function event()
    {
        return $this->belongsTo(ReferralEvent::class, 'referral_event_id');
    }

    public function beneficiary()
    {
        return $this->belongsTo(User::class, 'beneficiary_user_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeReversed($query)
    {
        return $query->where('status', 'reversed');
    }

    /**
     * Confirm the reward
     */
    public function confirm()
    {
        $this->update([
            'status' => 'confirmed',
            'credited_at' => now()
        ]);
    }

    /**
     * Reverse the reward
     */
    public function reverse($reason = null)
    {
        $this->update([
            'status' => 'reversed',
            'reason' => $reason
        ]);
    }
}
