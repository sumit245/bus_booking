<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'pnr_number', 'user_id', 'date_of_journey', 'seats', 'boarding_point',
        'dropping_point', 'amount', 'payment_id', 'status'
    ];

    protected $casts = [
        'seats' => 'array',
        'date_of_journey' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}