<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarkupTable extends Model
{
    protected $table = 'markup_table';

    protected $fillable = [
        'amount',
        'flat_markup',
        'percentage_markup',
        'threshold',
    ];

    public $timestamps = true;
}
