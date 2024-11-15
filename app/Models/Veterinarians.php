<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Veterinarians extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasUuids;

    protected $fillable = [
        'name',
        'email',
        'image',
        'password',
        'status',
        'position',
        'license_number',
        'phone_number',
        'electronic_signature',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}