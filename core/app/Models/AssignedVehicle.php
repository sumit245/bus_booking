<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignedVehicle extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function trip(){
        return $this->belongsTo(Trip::class);
    }

    public function vehicle(){
        return $this->belongsTo(Vehicle::class);
    }
    
     public function vehicleDetails()
 {
  return $this->belongsTo(Vehicle::class, 'vehicle_id');
 }
}
