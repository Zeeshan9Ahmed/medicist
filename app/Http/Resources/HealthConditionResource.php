<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthConditionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {   
        $trimed_ellergies = trim($this->ellergies,'[]');
        $trimed_diseases = trim($this->diseases,'[]');
        $trimed_symptoms = trim($this->symptoms,'[]');
        return [
            'id' => $this->id,
            'ellergies' => $trimed_ellergies?explode(', ', $trimed_ellergies):[],
            'diseases' => $trimed_diseases?explode(', ', $trimed_diseases):[],
            'symptoms' => $trimed_symptoms?explode(', ', $trimed_symptoms):[],
            'advice' => $this->advice,
            'images' => ImageResource::collection($this->report_images)
        ];
    }
}
