<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'height_feet',
        'height_inches',
        'weight',
        'weight_type',
        'address',
        'past_consultant_advice',
        'past_allergies',
        'past_diseases',
        'past_symptoms',
        'current_consultant_advice',
        'current_allergies',
        'current_diseases',
        'current_symptoms'
    ];

    protected function pastAllergies(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ucfirst($value),
            set: fn (string $value) => strtolower($value),
        );
    }
}
