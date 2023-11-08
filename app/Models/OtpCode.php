<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'email',
        'user_id',
        'ref',
        'expiring_at',
        'reference_code'
    ];
}
