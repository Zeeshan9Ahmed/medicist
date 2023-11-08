<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    use HasFactory;

    protected  $fillable = [
        'user_id',
        'specialty',
        'year_of_experience',
        'hospital_clinic',
        'appointment_type',
        'consultation_fee',
    ];
}
