<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BookedTicket extends Model
{
    use HasFactory;

    protected $casts = [
        // Note: source_destination removed from casts - manually json_encoded to match old format
        'seats' => 'array',
        'passenger_names' => 'array'
    ];

    protected $appends = ['photo'];

    protected $fillable = [
        'user_id',
        'operator_id',
        'operator_booking_id',
        'agent_id',
        'booking_id',
        'ticket_no',
        'gender',
        'trip_id',
        'source_destination',
        'dropping_point',
        'seats',
        'ticket_count',
        'unit_price',
        'sub_total',
        'total_amount',
        'paid_amount',
        'date_of_journey',
        'departure_time',
        'arrival_time',
        'pnr_number',
        'status',
        'payment_status',
        'booking_type',
        'booking_reason',
        'notes',
        'passenger_names',
        'passenger_name',
        'passenger_phone',
        'passenger_email',
        'passenger_address',
        'passenger_age',
        'passenger_phones',
        'passenger_emails',
        'api_response',
        'boarding_point_details',
        'dropping_point_details',
        'search_token_id',
        'operator_pnr',
        'bus_type',
        'travel_name',
        'bus_id',
        'route_id',
        'schedule_id',
        'api_invoice',
        'api_invoice_amount',
        'api_invoice_date',
        'api_booking_id',
        'api_ticket_no',
        'agent_commission',
        'agent_commission_amount',
        'booking_source',
        'total_commission_charged',
        'tds_from_api',
        'origin_city',
        'destination_city',
        'cancellation_policy',
        'cancellation_remarks',
        'cancelled_at',
        'bus_details',
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

    // Note: pickup() relationship - safely returns Counter model
    // Uses pickup_point column if available, otherwise returns null
    // The relationship is defined but will only work if the column exists
    // The controller checks for column existence before eager loading
    public function pickup()
    {
        return $this->belongsTo(Counter::class, 'pickup_point');
    }

    /**
     * Accessor for pickup - safely returns Counter model from various sources
     */
    public function getPickupAttribute()
    {
        // Try to get from JSON details first
        if ($this->boarding_point_details) {
            $details = json_decode($this->boarding_point_details, true);
            if (isset($details['CityPointIndex'])) {
                $counter = Counter::find($details['CityPointIndex']);
                if ($counter) {
                    return $counter;
                }
            }
        }
        
        // Fallback to pickup_point column if it exists
        if (isset($this->attributes['pickup_point']) && $this->attributes['pickup_point']) {
            return Counter::find($this->attributes['pickup_point']);
        }
        
        return null;
    }

    public function drop()
    {
        return $this->belongsTo(Counter::class, 'dropping_point');
    }

    /**
     * Accessor for drop - safely returns Counter model from various sources
     */
    public function getDropAttribute()
    {
        // Try to get from relationship first (most reliable)
        if ($this->relationLoaded('drop') && $this->getRelation('drop')) {
            return $this->getRelation('drop');
        }
        
        // Try to get from JSON details
        if ($this->dropping_point_details) {
            $details = json_decode($this->dropping_point_details, true);
            if (isset($details['CityPointIndex'])) {
                $counter = Counter::find($details['CityPointIndex']);
                if ($counter) {
                    return $counter;
                }
            }
        }
        
        // Fallback to dropping_point column
        if (isset($this->attributes['dropping_point']) && $this->attributes['dropping_point']) {
            return Counter::find($this->attributes['dropping_point']);
        }
        
        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function agentBooking()
    {
        return $this->hasOne(AgentBooking::class);
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
