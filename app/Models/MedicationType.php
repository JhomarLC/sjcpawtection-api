<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'status'
    ];

    public function mednames() : HasMany {
        return $this->hasMany(MedicationName::class);
    }
}
