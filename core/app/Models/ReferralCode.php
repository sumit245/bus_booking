<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReferralCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'source',
        'device_id',
        'total_clicks',
        'total_installs',
        'total_signups',
        'total_bookings',
        'total_earnings',
        'is_active'
    ];

    protected $casts = [
        'total_clicks' => 'integer',
        'total_installs' => 'integer',
        'total_signups' => 'integer',
        'total_bookings' => 'integer',
        'total_earnings' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Generate a unique 6-character alphanumeric code
     */
    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(ReferralEvent::class);
    }

    public function clicks()
    {
        return $this->hasMany(ReferralClick::class);
    }

    /**
     * Increment counters
     */
    public function incrementClicks()
    {
        $this->increment('total_clicks');
    }

    public function incrementInstalls()
    {
        $this->increment('total_installs');
    }

    public function incrementSignups()
    {
        $this->increment('total_signups');
    }

    public function incrementBookings()
    {
        $this->increment('total_bookings');
    }

    public function addEarnings($amount)
    {
        $this->increment('total_earnings', $amount);
    }
}
