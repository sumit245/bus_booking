<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookedTicket extends Model
{
    use HasFactory;

    protected $casts = [
        'source_destination' => 'array',
        'seats' => 'array'
    ];

    protected $appends = ['photo'];
    
     protected $fillable = [
  'user_id',
  'gender',
  'trip_id',
  'source_destination',
  'pickup_point',
  'dropping_point',
  'seats',
  'ticket_count',
  'unit_price',
  'sub_total',
  'date_of_journey',
  'pnr_number',
  'status',
  'passenger_names', // Include additional fields if applicable
 ];


    public function getPhotoAttribute(){
    return $this->where('status', 0);
    }

    public function trip(){
        return $this->belongsTo(Trip::class);
    }
    public function pickup(){
        return $this->belongsTo(Counter::class, 'pickup_point');
    }
    public function drop(){
        return $this->belongsTo(Counter::class, 'dropping_point');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    //scope
    public function scopePending(){
        return $this->where('status', 2);
    }

    public function scopeBooked(){
        return $this->where('status', 1);
    }

    public function scopeRejected(){
        return $this->where('status', 0);
    }
}
