<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PetPhotos extends Model
{
    use HasFactory;

    public function pet(): BelongsTo {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

}