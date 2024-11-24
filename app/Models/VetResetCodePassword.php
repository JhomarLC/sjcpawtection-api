<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VetResetCodePassword extends Model
{
    protected $fillable = [
        'email',
        'code',
        'created_at',
    ];
}