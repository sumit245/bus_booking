<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponTable extends Model
{
    protected $table = 'coupon_table';

    protected $fillable = [
        'coupon_name',
        'coupon_threshold',
        'discount_type',          // New: 'fixed' or 'percentage'
        'coupon_value',           // New: Replaces flat_coupon_amount and percentage_coupon_amount
        'expiry_date',            // New: Date when coupon expires
        'status',                 // New: 0 for deactivated, 1 for active
    ];

    protected $casts = [
        'expiry_date' => 'datetime',
        'status' => 'boolean',
    ];

    public $timestamps = true;
}
