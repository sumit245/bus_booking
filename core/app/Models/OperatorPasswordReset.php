<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatorPasswordReset extends Model
{
    protected $table = 'operator_password_resets';

    protected $fillable = [
        'email',
        'token',
        'status',
        'created_at'
    ];

    public $timestamps = false;
}
