<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleRoute extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $casts = [
        'stoppages' => 'array'
    ];

    public function startFrom(){
        return $this->belongsTo(Counter::class, 'start_from', 'id');
    }


    public function endTo(){
        return $this->belongsTo(Counter::class, 'end_to', 'id');
    }

    //scope
    public function scopeActive(){
      return  $this->where('status', 1);
    }
}
