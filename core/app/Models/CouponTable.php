<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponTable extends Model
{
    protected $table = 'coupon_table';
    
    protected $fillable = [
        'coupon_name',
        'coupon_amount',
    ];
    
    public $timestamps = true;
}
