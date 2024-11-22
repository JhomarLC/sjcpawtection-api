<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VetNotificationTokens extends Model
{
    protected $fillable = [
        'veterinarians_id',
        'token',
    ];

    public function veterinarian() : BelongsTo {
        return $this->belongsTo(Veterinarians::class, 'veterinarians_id');
    }

}
