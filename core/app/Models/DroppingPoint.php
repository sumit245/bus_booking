<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DroppingPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_route_id',
        'bus_schedule_id',
        'point_name',
        'point_address',
        'point_location',
        'point_landmark',
        'contact_number',
        'point_index',
        'point_time',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'point_time' => 'datetime:H:i'
    ];

    /**
     * Get the operator route that owns this dropping point.
     */
    public function operatorRoute()
    {
        return $this->belongsTo(OperatorRoute::class);
    }

    /**
     * Get the bus schedule that owns this dropping point.
     */
    public function busSchedule()
    {
        return $this->belongsTo(BusSchedule::class);
    }

    /**
     * Scope a query to only include active dropping points.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query for a specific schedule.
     */
    public function scopeForSchedule($query, $scheduleId)
    {
        return $query->where('bus_schedule_id', $scheduleId);
    }

    /**
     * Scope a query for a specific route.
     */
    public function scopeForRoute($query, $routeId)
    {
        return $query->where('operator_route_id', $routeId)->whereNull('bus_schedule_id');
    }

    /**
     * Scope a query to order by point index.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('point_index');
    }

    /**
     * Get formatted time.
     */
    public function getFormattedTimeAttribute()
    {
        return $this->point_time ? $this->point_time->format('H:i') : 'N/A';
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute()
    {
        $parts = array_filter([
            $this->point_address,
            $this->point_location,
            $this->point_landmark
        ]);

        return implode(', ', $parts);
    }
}