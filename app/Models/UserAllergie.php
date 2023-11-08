<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAllergie extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'tag'
    ];
}
