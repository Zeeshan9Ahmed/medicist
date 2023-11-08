<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoggedInUser extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // dd(1, $this->health_condition->where('type','past')->first());
        return [
            'id' => $this->id,
            'email' => $this->email ?? "",
            'user_type' => $this->role ?? "",
            'first_name' => $this->when($this->role == "user" || $this->role == "doctor", $this->first_name ?? ""),
            'dob' => $this->when($this->role == "user" || $this->role == "doctor", $this->dob ?? ""),
            'last_name' => $this->when($this->role == "user" || $this->role == "doctor", $this->last_name ?? ""),
            'pharmacy_name' => $this->when($this->role == "pharmacy", $this->first_name ?? ""),
            'labortory_name' => $this->when($this->role == "labortory", $this->first_name ?? ""),
            'avatar' => $this->avatar ?? "",
            'gender' => $this->when($this->role == "user" || $this->role == "doctor", $this->gender ?? ""),
            'height_feet' => $this->when($this->role == "user", $this->height_feet ?? ""),
            'step' => $this->when($this->role == "user", $this->profile_completed == 1 ? 4 : 1),
            'height_inch' => $this->when($this->role == "user", $this->height_inch ?? ""),
            'weight' => $this->when($this->role == "user", $this->weight ?? ""),
            'weight_type' => $this->when($this->role == "user", $this->weight_type ?? ""),
            'language' => $this->when($this->role == "doctor", $this->language ?? ""),
            'profile_completed' => $this->profile_completed ?? "",
            'is_verified' => $this->email_verified_at ? 1 : 0,
            'phone_number' => $this->phone_number ?? "",
            'is_social' => $this->is_social ? 1 : 0,
            'address' => $this->address ?? "",
            'city' => $this->city ?? "",
            'state' => $this->state ?? "",
            'zip_code' => $this->zip_code ?? "",
            'emergency_contacts' => $this->when($this->role == "user", EmergencyContactsResource::collection($this->emergency_contacts)),
            'past_condition' => $this->when($this->role == "user", new HealthConditionResource($this->health_condition->where('type', 'past')->first())),
            'present_condition' => $this->when($this->role == "user", new HealthConditionResource($this->health_condition->where('type', 'present')->first())),
            'work_profile' => $this->when(
                $this->role == "doctor",
                [
                    'speciality' => $this->doctor_profile?->specialty ?? "",
                    'year_of_experience' => $this->doctor_profile?->year_of_experience ?? "",
                    'hospital_clinic' => $this->doctor_profile?->hospital_clinic ?? "",
                    'appointment_type' => $this->doctor_profile?->appointment_type ?? "",
                    'consultation_fee' => $this->doctor_profile?->consultation_fee ?? 0,
                    'schedule' => ScheduleResource::collection($this->schedule),
                    'certificates' => ImageResource::collection($this->certificates),
                ]
            ),
            'schedule' => $this->when($this->role == 'pharmacy' || $this->role == "labortory", ScheduleResource::collection($this->schedule)),
            'certificates' => $this->when($this->role == 'pharmacy' || $this->role == "labortory", ImageResource::collection($this->certificates)),
            'additional_data' => $this->when($this->role == 'pharmacy' || $this->role == "labortory",  new AdditionalDataResource($this->additional_data))
        ];
    }
}
