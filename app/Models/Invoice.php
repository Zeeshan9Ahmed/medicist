<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function doctor () {
        return $this->belongsTo(User::class, 'doctor_id','id')
                    ->select('id', 'first_name','last_name','avatar')
                    ->selectRaw('(select specialty from doctor_profiles where user_id = users.id LIMIT 1) as specialty');
    }
}
