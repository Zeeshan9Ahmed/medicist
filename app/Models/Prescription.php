<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function doctor () {
        return $this->belongsTo(User::class,'doctor_id','id')
                        // ->select('id', 'first_name','last_name','email','avatar','address','city','state','zip_code','language','gender')
                        ;
    }

    public function appointment () {
        return $this->belongsTo(Appointment::class,'appointment_id','id')->selectRaw('1 as date');
    }

    
}
