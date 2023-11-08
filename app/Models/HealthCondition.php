<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCondition extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ellergies',
        'diseases',
        'symptoms',
        'advice',
        'type',
    ];

    public function report_images () {
        return $this->hasMany(Image::class,'table_id','id')
                        ->where(['table_name' =>'health_conditions','image_type' => 'report']);
    }
}
