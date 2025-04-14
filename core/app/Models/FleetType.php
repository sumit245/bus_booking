<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetType extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'deck_seats' => 'object',
        'facilities' => 'array'
    ];

    public function vehicles(){
        return $this->hasMany(Vehicle::class);
    }

    public function activeVehicles(){
        return $this->hasMany(Vehicle::class)->where('status', 1);
    }

    //scope active
    public function scopeActive(){
        return $this->where('status', 1);
    }
}
