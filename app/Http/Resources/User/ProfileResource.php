<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'height_feet'                   =>  $this->height_feet,
            'height_inches'                 =>  $this->height_inches,
            'weight'                        =>  $this->weight,
            'weight_type'                   =>  $this->weight_type,
            'address'                       =>  $this->address,
            'past_consultant_advice'        =>  $this->past_consultant_advice,
            'past_allergies'                =>  $this->past_allergies,
            'past_diseases'                 =>  $this->past_diseases,
            'past_symptoms'                 =>  $this->past_symptoms,
            'current_consultant_advice'     =>  $this->current_consultant_advice,
            'current_allergies'             =>  $this->current_allergies,
            'current_diseases'              =>  $this->current_diseases,
            'current_symptoms'              =>  $this->current_symptoms
        ];
    }
}
