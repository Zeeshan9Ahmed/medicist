<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdditionalDataResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'contact_person' => $this->contact_person??"",
            'contact_number' => $this->contact_number??"",
            'image' => $this->image??"",
            'years_of_experience' => $this->years_of_experience??"",
            'license_number' => $this->license_number??"",
        ];
    }
}
