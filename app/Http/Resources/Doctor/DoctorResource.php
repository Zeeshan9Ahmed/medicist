<?php

namespace App\Http\Resources\Doctor;

use App\Models\DoctorAvailability;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                            =>  $this->id,
            'first_name'                    =>  $this->first_name,
            'last_name'                     =>  $this->last_name,
            'email'                         =>  $this->email,
            'gender'                        =>  $this->gender,
            'profile_image'                 =>  $this->profile_image,
            'is_profile_complete'           =>  $this->is_profile_complete,
            'is_verified'                   =>  $this->is_verified,
            'user_type'                     =>  $this->user_type,
            'profile'                       =>  [
                'language'                      =>  $this->doctor_profile->language??null,
                'date_of_birth'                 =>  $this->doctor_profile->date_of_birth??null,
                'phone_number'                  =>  $this->doctor_profile->phone_number??null,
                'address'                       =>  $this->doctor_profile->address??null,
                'city'                          =>  $this->doctor_profile->city??null,
                'zip_code'                      =>  $this->doctor_profile->zip_code??null,
                'state'                         =>  $this->doctor_profile->state??null,
                'specialty'                     =>  $this->doctor_profile->specialty??null,
                'year_of_experience'            =>  $this->doctor_profile->year_of_experience??null,
                'hospital_clinic'               =>  $this->doctor_profile->hospital_clinic??null,
                'appointment_type'              =>  $this->doctor_profile->appointment_type??null,
                'consultation_fee'              =>  $this->doctor_profile->consultation_fee??null
            ],
            'availability'                  =>  AvailabilityResource::collection($this->doctor_availability)
        ];
    }
}
