<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medications extends Model
{
    use HasFactory;

    protected $fillable = [
        'medication_name_id',
        'veterinarians_id',
        'pet_id',
        'batch_number',
        'expiry_date',
        'next_vaccination',
        'medication_date',
        'or_number',
        'fee',
        'remarks',
    ];

    public function medicationname() : BelongsTo {
        return $this->belongsTo(MedicationName::class, 'medication_name_id');
    }

    public function pet() : BelongsTo {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

    public function veterinarian() : BelongsTo {
        return $this->belongsTo(Veterinarians::class, 'veterinarians_id');
    }
}