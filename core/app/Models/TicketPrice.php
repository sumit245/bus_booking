<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketPrice extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function fleetType(){
        return $this->belongsTo(FleetType::class);
    }

    public function route(){
        return $this->belongsTo(VehicleRoute::class , 'vehicle_route_id');
    }

    public function prices(){
        return $this->hasMany(TicketPriceByStoppage::class);
    }
}
