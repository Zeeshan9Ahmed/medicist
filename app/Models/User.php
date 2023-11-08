<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'gender',
        'profile_image',
        'user_type',
        'is_profile_complete',
        'device_type',
        'device_token',
        'social_type',
        'social_token',
        'is_forgot',
        'is_verified',
        'verified_code',
        'is_active',
        'is_blocked',
        'role',
        'phone_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function emergency_contacts()
    {
        return $this->hasMany(EmergencyContact::class, 'user_id')
                        ->select('id','user_id','first_name','last_name','contact_number','relation');
    }

    public function user_profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    public function doctor_profile()
    {
        return $this->hasOne(DoctorProfile::class, 'user_id');
    }

    public function schedule()
    {
        return $this->hasMany(Schedule::class, 'user_id');
    }

    public function health_condition () {
        return $this->hasMany(HealthCondition::class, 'user_id');
    }

    public function certificates () {
        return $this->hasMany(Image::class,'table_id','id')
                        ->where(['table_name' =>'users','image_type' => 'certificates']);
    }

    public function additional_data () {
        return $this->hasOne(LabortoryPharmacyInformation::class,'user_id');
    }

    public function reviews () {
        
    }
}
