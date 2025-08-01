<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponTable extends Model
{
    protected $table = 'coupon_table';
    
    protected $fillable = [
        'coupon_name',
        'coupon_threshold',        // New field
        'flat_coupon_amount',      // New field
        'percentage_coupon_amount',// New field
        // 'coupon_amount' is removed as it's replaced by flat/percentage
    ];
    
    public $timestamps = true;
}
