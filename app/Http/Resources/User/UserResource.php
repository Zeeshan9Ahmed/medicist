<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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

            'address'                       =>  $this->address,
            'city'                          =>  $this->city,
            'zip_code'                      =>  $this->zip_code,
            'state'                         =>  $this->state,


            'profile'                       =>  [
                'date_of_birth'                 =>  $this->user_profile->date_of_birth??null,
                'height_feet'                   =>  $this->user_profile->height_feet??null,
                'height_inches'                 =>  $this->user_profile->height_inches??null,
                'weight'                        =>  $this->user_profile->weight??null,
                'weight_type'                   =>  $this->user_profile->weight_type??null,
                'address'                       =>  $this->user_profile->address??null,
                'past_consultant_advice'        =>  $this->user_profile->past_consultant_advice??null,
                'past_allergies'                =>  $this->user_profile->past_allergies??null,
                'past_diseases'                 =>  $this->user_profile->past_diseases??null,
                'past_symptoms'                 =>  $this->user_profile->past_symptoms??null,
                'current_consultant_advice'     =>  $this->user_profile->current_consultant_advice??null,
                'current_allergies'             =>  $this->user_profile->current_allergies??null,
                'current_diseases'              =>  $this->user_profile->current_diseases??null,
                'current_symptoms'              =>  $this->user_profile->current_symptoms??null,
            ],
            'emergency_contact'             =>  EmergencyContactResource::collection($this->emergency_contact)
        ];
    }
}
