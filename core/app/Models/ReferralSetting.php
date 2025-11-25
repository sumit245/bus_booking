<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_enabled',
        'reward_type',
        'fixed_amount',
        'percent_share',
        'percent_of_ticket',
        'reward_on_install',
        'reward_on_signup',
        'reward_on_first_booking',
        'reward_referrer',
        'reward_referee',
        'min_booking_amount',
        'reward_credit_days',
        'daily_cap_per_referrer',
        'max_referrals_per_user',
        'share_message',
        'terms_and_conditions',
        'notes'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'reward_on_install' => 'boolean',
        'reward_on_signup' => 'boolean',
        'reward_on_first_booking' => 'boolean',
        'reward_referrer' => 'boolean',
        'reward_referee' => 'boolean',
        'fixed_amount' => 'decimal:2',
        'percent_share' => 'decimal:2',
        'percent_of_ticket' => 'decimal:2',
        'min_booking_amount' => 'decimal:2',
        'reward_credit_days' => 'integer',
        'daily_cap_per_referrer' => 'integer',
        'max_referrals_per_user' => 'integer'
    ];

    /**
     * Get the singleton instance
     */
    public static function current()
    {
        return static::first() ?? static::create([
            'is_enabled' => true,
            'reward_type' => 'percent_of_ticket',
            'percent_of_ticket' => 10,
            'reward_on_first_booking' => true,
            'reward_referrer' => true,
            'min_booking_amount' => 100,
            'share_message' => 'Join Ghumantoo and get amazing bus booking deals! Use my referral code to get started.'
        ]);
    }
}
