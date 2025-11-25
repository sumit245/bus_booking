<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_user_id',
        'referee_user_id',
        'referral_code_id',
        'type',
        'ticket_id',
        'context_json',
        'triggered_at'
    ];

    protected $casts = [
        'context_json' => 'array',
        'triggered_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }

    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }

    public function ticket()
    {
        return $this->belongsTo(BookedTicket::class, 'ticket_id');
    }

    public function rewards()
    {
        return $this->hasMany(ReferralReward::class);
    }
}
