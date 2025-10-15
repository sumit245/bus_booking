<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $fillable = [
        'city_id',
        'city_name'
    ];

    /**
     * Get the routes that originate from this city.
     */
    public function originRoutes()
    {
        return $this->hasMany(OperatorRoute::class, 'origin_city_id');
    }

    /**
     * Get the routes that terminate in this city.
     */
    public function destinationRoutes()
    {
        return $this->hasMany(OperatorRoute::class, 'destination_city_id');
    }

    /**
     * Get all routes that pass through this city (both origin and destination).
     */
    public function allRoutes()
    {
        return OperatorRoute::where('origin_city_id', $this->id)
            ->orWhere('destination_city_id', $this->id);
    }
}
