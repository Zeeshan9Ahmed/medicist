<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientRequest extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function user () {
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function prescription () {
        return $this->belongsTo(Prescription::class,'prescription_id','id');
    }
}
