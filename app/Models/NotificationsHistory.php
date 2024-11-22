<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationsHistory extends Model
{
    //
    protected $fillable = [
        'title', 'description', 'action'
    ];
}
