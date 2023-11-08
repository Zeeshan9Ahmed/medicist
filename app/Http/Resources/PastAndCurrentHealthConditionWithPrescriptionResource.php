<?php

namespace App\Http\Resources;

use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PastAndCurrentHealthConditionWithPrescriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $past = $this->where('type','past')->first();

        $user_id = $past?->user_id;
        $prescriptions = Prescription::with('doctor:id,first_name,last_name','appointment:id,start_time,end_time,date')
                                ->select('id','appointment_id','doctor_id','fitness', 'medical_advice','lab_advice','prescription')
                                ->where('user_id', 3??$user_id)->get();
        return [
            'prescriptions' => $prescriptions,
            'past_condition' => new HealthConditionResource($past),
            'present_condition' => new HealthConditionResource($this->where('type','present')->first()),
        ];
    }
}
