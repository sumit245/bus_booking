<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    protected $guarded = ['id'];

    //scope
    public function scopeActive(){
        return $this->where('status', 1);
    }

    public function scopeRouteStoppages($query, $array)
    {
        return $query->whereIn('id', $array)
        ->orderByRaw("field(id,".implode(',',$array).")")->get();
    }
}
