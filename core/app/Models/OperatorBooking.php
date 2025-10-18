<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OperatorBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'operator_bus_id',
        'operator_route_id',
        'bus_schedule_id',
        'blocked_seats',
        'total_seats_blocked',
        'journey_date',
        'journey_date_end',
        'is_date_range',
        'booking_reason',
        'notes',
        'status',
        'blocked_amount'
    ];

    protected $casts = [
        'blocked_seats' => 'array',
        'journey_date' => 'date',
        'journey_date_end' => 'date',
        'is_date_range' => 'boolean',
        'blocked_amount' => 'decimal:2'
    ];

    // Relationships
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    public function operatorBus()
    {
        return $this->belongsTo(OperatorBus::class);
    }

    public function operatorRoute()
    {
        return $this->belongsTo(OperatorRoute::class);
    }

    public function busSchedule()
    {
        return $this->belongsTo(BusSchedule::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->where('journey_date', $date)
                ->orWhere(function ($q2) use ($date) {
                    $q2->where('is_date_range', true)
                        ->where('journey_date', '<=', $date)
                        ->where('journey_date_end', '>=', $date);
                });
        });
    }

    public function scopeForBus($query, $busId)
    {
        return $query->where('operator_bus_id', $busId);
    }

    // Accessors
    public function getDateRangeAttribute()
    {
        if ($this->is_date_range && $this->journey_date_end) {
            return $this->journey_date->format('M d, Y') . ' - ' . $this->journey_date_end->format('M d, Y');
        }
        return $this->journey_date->format('M d, Y');
    }

    public function getSeatsListAttribute()
    {
        return is_array($this->blocked_seats) ? implode(', ', $this->blocked_seats) : $this->blocked_seats;
    }

    // Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isDateRange()
    {
        return $this->is_date_range && $this->journey_date_end;
    }

    public function coversDate($date)
    {
        $checkDate = Carbon::parse($date)->toDateString();

        if ($this->is_date_range && $this->journey_date_end) {
            return $checkDate >= $this->journey_date->toDateString() &&
                $checkDate <= $this->journey_date_end->toDateString();
        }

        return $checkDate === $this->journey_date->toDateString();
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function activate()
    {
        $this->update(['status' => 'active']);
    }
}
