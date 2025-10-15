<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoardingPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'operator_route_id',
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
     * Get the operator route that owns this boarding point.
     */
    public function operatorRoute()
    {
        return $this->belongsTo(OperatorRoute::class);
    }

    /**
     * Scope a query to only include active boarding points.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
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