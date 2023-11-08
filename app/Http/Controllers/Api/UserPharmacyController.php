<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PatientRequest;
use App\Models\User;
use App\Services\Notifications\CreateDBNotification;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\Request;

class UserPharmacyController extends Controller
{
    public function pharmacies(Request $request)
    {
        $name = $request->name;
        $location = $request->location;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $minDistance = $request->min_distance;
        $maxDistance = $request->max_distance;
        $experience_from = $request->experience_from;
        $experience_to = $request->experience_to;

        $pharmacies = User::with(['additional_data','schedule'])->whereRole('pharmacy')
            ->select('id', 'first_name as pharmacy_name', 'avatar', 'state', 'address', 'city')
            ->selectRaw(' (select CASE WHEN count(id) > 0 THEN 1 ELSE 0 END from 
                                        images where images.table_id = users.id AND table_name = "users" AND 
                                        image_type ="certificates" ) as is_certified,
                            (SELECT AVG (rating) from reviews where rating_user_id = users.id) as avg_rating ,
                            "1.3K" as successfull_patients
                        ')
            ->selectRaw('( 6371 * acos( cos( radians(?) ) *
                        cos( radians( latitude ) )
                        * cos( radians( longitude ) - radians(?)
                        ) + sin( radians(?) ) *
                        sin( radians( latitude ) ) )
                    ) AS distance', [$latitude, $longitude, $latitude])
            ->when($minDistance && $maxDistance && $location, function ($query) use ($minDistance, $maxDistance) {
                $query->havingRaw('distance BETWEEN ? AND ?', [$minDistance, $maxDistance]);
            })
            ->when($name, function ($query) use ($name) {
                $query->where('first_name', 'LIKE', "%$name%");
            })
            ->whereHas('additional_data', function ($query) use($experience_from , $experience_to) {
                $query->when($experience_from && $experience_to , function ($query) use($experience_from , $experience_to) {

                    $query->whereBetween('years_of_experience', [$experience_from, $experience_to]);
                });
            })
            ->whereProfileCompleted(true)
            ->whereRaw('id not  in (select report_user_id from reports where user_id = ' . auth()->id() . ')')
            ->get();
        return apiSuccessMessage("Pharmacy", $pharmacies);
    }

    public function sendPrescriptionRequest(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|in:labortory,pharmacy',
            'other_user_id' => 'required|exists:users,id',
            'prescription_id' => 'required|exists:prescriptions,id',
        ]);

        PatientRequest::create($request->only(['type', 'other_user_id', 'prescription_id']) + ['user_id' => auth()->id()]);

        $message = auth()->user()->first_name . " " .auth()->user()->last_name. " has sent you a prescription request.";
        $user = User::whereId($request->other_user_id)->first();
        $token = $user->device_token;
        $data = [
            'to_user_id'        =>  $user->id,
            'from_user_id'      =>  auth()->id(),
            'notification_type' =>  'CANCEL_APPOINTMENT',
            'title'             =>  $message,
            'description'        =>$message,
            'redirection_id'    =>   $user->id
        ];


        app(CreateDBNotification::class)->execute($data);
        if ($token) {
            app(PushNotificationService::class)->execute($data,[$token]);
        }
        return commonSuccessMessage("Request Sent");
    }

    public function prescriptionRequests(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|in:labortory,pharmacy'
        ]);
    }
}
