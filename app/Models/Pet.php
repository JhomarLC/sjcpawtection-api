<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Pet extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'image',
        'breed',
        'color_description',
        'size',
        'weight',
        'date_of_birth',
        'status',
        'pet_owner_id',
        'pet_type'
    ];

    public function petowner() : BelongsTo {
        return $this->belongsTo(PetOwner::class, 'pet_owner_id');
    }

    public function petphotos() : HasMany {
        return $this->hasMany(PetPhotos::class);
    }

    public function medications() : HasMany {
        return $this->hasMany(Medications::class);
    }

    public function getFormattedAgeAttribute()
    {
        $dateOfBirth = Carbon::parse($this->date_of_birth);
        $now = Carbon::now();
        $ageInDays = abs($now->diffInDays($dateOfBirth));

        $months = floor($ageInDays / 30);
        $days = $ageInDays % 30;

        $ageParts = [];
        if ($months > 0) {
            $ageParts[] = $months . ' ' . ($months === 1 ? 'month' : 'months');
        }
        if ($days > 0) {
            $ageParts[] = $days . ' ' . ($days === 1 ? 'day' : 'days');
        }

        if ($ageInDays === 0) {
            return 'less than a day old';
        }

        return !empty($ageParts) ? implode(' and ', $ageParts) . ' old' : 'less than a day old';
    }

}