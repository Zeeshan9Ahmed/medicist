<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabortoryPharmacyInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contact_person',
        'contact_number',
        'image',
        'years_of_experience',
        'license_number',
    ];
}
