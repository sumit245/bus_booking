<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BookedTicket extends Model
{
    use HasFactory;

    protected $casts = [
        'source_destination' => 'array',
        'seats' => 'array',
        'passenger_names' => 'array'
    ];

    protected $appends = ['photo'];

    protected $fillable = [
        'user_id',
        'gender',
        'trip_id',
        'source_destination',
        'pickup_point',
        'dropping_point',
        'seats',
        'ticket_count',
        'unit_price',
        'sub_total',
        'date_of_journey',
        'pnr_number',
        'status',
        'passenger_names',
        'passenger_name',
        'passenger_phone',
        'passenger_email',
        'passenger_address',
        'passenger_age',
        'api_response',
        'boarding_point_details',
        'dropping_point_details',
        'search_token_id',
        'operator_pnr',
        'bus_type',
        'travel_name',
        'api_invoice',
        'api_invoice_amount',
        'api_invoice_date',
        'api_booking_id',
        'api_ticket_no',
        'agent_commission',
        'tds_from_api',
        'origin_city',
        'destination_city',
        'cancellation_policy',
    ];

    // Add date mutator to fix invalid dates
    public function getDateOfJourneyAttribute($value)
    {
        if ($value == '0000-00-00' || is_null($value)) {
            return date('Y-m-d');
        }
        return $value;
    }

    public function getPhotoAttribute()
    {
        return $this->where('status', 0);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function pickup()
    {
        return $this->belongsTo(Counter::class, 'pickup_point');
    }

    public function drop()
    {
        return $this->belongsTo(Counter::class, 'dropping_point');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //scope
    public function scopePending()
    {
        return $this->where('status', 2);
    }

    public function scopeBooked()
    {
        return $this->where('status', 1);
    }

    public function scopeRejected()
    {
        return $this->where('status', 0);
    }
}
