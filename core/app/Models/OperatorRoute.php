<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatorRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_id',
        'route_name',
        'origin_city_id',
        'destination_city_id',
        'description',
        'distance',
        'estimated_duration',
        'base_fare',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'distance' => 'decimal:2',
        'base_fare' => 'decimal:2',
        'estimated_duration' => 'decimal:1'
    ];

    /**
     * Get the operator that owns the route.
     */
    public function operator()
    {
        return $this->belongsTo(Operator::class);
    }

    /**
     * Get the origin city.
     */
    public function originCity()
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    /**
     * Get the destination city.
     */
    public function destinationCity()
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    /**
     * Get the boarding points for this route.
     */
    public function boardingPoints()
    {
        return $this->hasMany(BoardingPoint::class)->orderBy('point_index');
    }

    /**
     * Get the dropping points for this route.
     */
    public function droppingPoints()
    {
        return $this->hasMany(DroppingPoint::class)->orderBy('point_index');
    }

    /**
     * Get all buses currently assigned to this route.
     */
    public function assignedBuses()
    {
        return $this->hasMany(OperatorBus::class, 'current_route_id');
    }

    /**
     * Get the bus schedules for this route.
     */
    public function busSchedules()
    {
        return $this->hasMany(BusSchedule::class);
    }

    /**
     * Get active buses currently assigned to this route.
     */
    public function activeAssignedBuses()
    {
        return $this->assignedBuses()->where('status', 1);
    }

    /**
     * Scope a query to only include active routes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include routes for a specific operator.
     */
    public function scopeForOperator($query, $operatorId)
    {
        return $query->where('operator_id', $operatorId);
    }

    /**
     * Get the route display name.
     */
    public function getDisplayNameAttribute()
    {
        return $this->route_name ?:
            ($this->originCity->city_name ?? 'Unknown') . ' to ' .
            ($this->destinationCity->city_name ?? 'Unknown');
    }

    /**
     * Get formatted distance.
     */
    public function getFormattedDistanceAttribute()
    {
        return $this->distance ? $this->distance . ' km' : 'N/A';
    }

    /**
     * Get formatted base fare.
     */
    public function getFormattedBaseFareAttribute()
    {
        return $this->base_fare ? '₹' . number_format((float) $this->base_fare, 2) : 'N/A';
    }

    /**
     * Get formatted estimated duration.
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->estimated_duration) {
            return 'N/A';
        }

        $duration = (float) $this->estimated_duration;
        $hours = floor($duration);
        $minutes = ($duration - $hours) * 60;

        if ($minutes > 0) {
            return $hours . 'h ' . round($minutes) . 'm';
        }

        return $hours . 'h';
    }
}