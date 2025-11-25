<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_code_id',
        'ip',
        'user_agent',
        'referer_url',
        'clicked_at'
    ];

    protected $casts = [
        'clicked_at' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function referralCode()
    {
        return $this->belongsTo(ReferralCode::class);
    }
}
