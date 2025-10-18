<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusRouteHistory extends Model
{
    use HasFactory;

    protected $table = 'bus_route_history';

    protected $fillable = [
        'bus_id',
        'route_id',
        'assigned_date',
        'unassigned_date',
        'notes'
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'unassigned_date' => 'date'
    ];

    /**
     * Get the bus that this history belongs to.
     */
    public function bus()
    {
        return $this->belongsTo(OperatorBus::class, 'bus_id');
    }

    /**
     * Get the route that this history belongs to.
     */
    public function route()
    {
        return $this->belongsTo(OperatorRoute::class, 'route_id');
    }

    /**
     * Scope to get current assignments (not unassigned).
     */
    public function scopeCurrent($query)
    {
        return $query->whereNull('unassigned_date');
    }

    /**
     * Scope to get historical assignments.
     */
    public function scopeHistorical($query)
    {
        return $query->whereNotNull('unassigned_date');
    }

    /**
     * Get the duration of this assignment.
     */
    public function getDurationAttribute()
    {
        $endDate = $this->unassigned_date ?? now();
        return $this->assigned_date->diffInDays($endDate);
    }

    /**
     * Check if this is an active assignment.
     */
    public function getIsActiveAttribute()
    {
        return is_null($this->unassigned_date);
    }
}