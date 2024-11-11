<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicationName extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status'
    ];

    public function medications() : HasMany {
        return $this->hasMany(Medications::class);
    }

    public function medtype() : BelongsTo {
        return $this->belongsTo(MedicationType::class, 'medication_type_id');
    }
}
