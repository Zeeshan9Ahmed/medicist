<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $guarded = [];


    // public function setDateAttribute($value)
    // {
    //     $this->attributes['date'] = Carbon::createFromFormat('m/d/Y', $value)->format('Y-m-d');
    // }

    // public function getDateAttribute($value)
    // {
    //     return Carbon::createFromFormat('Y-m-d', $value)->format('m/d/Y');
    // }

    public function doctor () {
        return $this->belongsTo(User::class, 'doctor_id','id')
                    ->select('id', 'first_name','last_name','avatar')
                    ->selectRaw('(select specialty from doctor_profiles where user_id = users.id LIMIT 1) as specialty');
    }

    public function user () {
        return $this->belongsTo(User::class, 'user_id','id')
                    ->select('id', 'first_name','last_name','avatar','state')
                    ;
    }

    public function prescription () {
        return $this->hasOne(Prescription::class, 'appointment_id', 'id');
    }

    

}
