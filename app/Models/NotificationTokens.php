<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationTokens extends Model
{
    use HasFactory;

    protected $fillable = [
        'pet_owner_id',
        'token',
    ];

    public function petowner() : BelongsTo {
        return $this->belongsTo(PetOwner::class, 'pet_owner_id');
    }
}