<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\PatientRequest;
use App\Models\User;
use App\Services\Notifications\CreateDBNotification;
use App\Services\Notifications\PushNotificationService;
use App\Services\Payment\PaymentService;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;

class LaboratoryPharmacyController extends Controller
{

    function getTransitionHour($timezone)
    {
        $dateTimeZone = new DateTimeZone($timezone);
        $transitionInfo = $dateTimeZone->getTransitions();

        // Iterate through transition rules to find the DST transition hour
        foreach ($transitionInfo as $transition) {
            // Check if the transition is DST and if the current time is before the transition
            if ($transition['isdst'] && Carbon::now($timezone)->lt(Carbon::parse($transition['time']))) {
                return Carbon::parse($transition['time'])->hour;
            }
        }

        // If no transition is found, return a default value (e.g., 19)
        return 19;
    }

    public function test(PaymentService $paymentService, Request $request)
    {
        $timezone = 'Asia/Karachi';
        $transitionHour = $this->getTransitionHour($timezone);

        $futureDateTimeString = '2023-10-16';
        // Get the current local time in Karachi timezone
        $localTime = new DateTime($futureDateTimeString, new DateTimeZone($timezone));

        // Set the local time to 18:00 (6 PM)
        // $localTime->setTime(19, 0, 0);

        // return $transitionHour;
        // Get the current hour
        $currentHour = (int) $localTime->format('H');

        // Define the transition hour
        // $transitionHour = 19; // 7 PM

        // // Check if the local time is after 19:00 (7 PM)


        // // Output the UTC time in your desired format
        // return $localTime->format('Y-m-d H:i:s');
        $localTime->setTimezone(new DateTimeZone('UTC'));
        $utc = $localTime->format('Y-m-d H:i:s');
        $utcDate = $localTime->format('m/d/Y');
        $utcTime = $localTime->format('H:i:s');
        if ($currentHour < $transitionHour) {
            // If it's before 19:00, adjust UTC time to the previous day and set the time to 19:00
            $localTime->modify('-1 day');
            $localTime->setTime($transitionHour, 0, 0);
        } else {
            // If it's 19:00 or later, set the time to 19:00 on the same day
            $localTime->setTime($transitionHour, 0, 0);
        }

        // Set the UTC timezone
        // $localTime->setTimezone(new DateTimeZone('UTC'));

        // Output the UTC time in your desired format
        return [$localTime->format('Y-m-d H:i:s'),  $utc];

        $type = $request->type ?? "upcoming";
        // return $type;
        $currentDateTime = $localTime->format('m/d/Y');
        $d = $localTime->format('Y-m-d');
        // return $d;
        $date = $request->date;
        $currentTime =  $utcTime ?? $localTime->format('H:i:s');
        // return [$currentDateTime, $currentTime, $utcDate, $utcTime, $localTime->format('Y-m-d H:i:s')];

        $appointments = Appointment::with('user')
            ->select('id', 'user_id', 'date', 'start_time', 'end_time', 'is_resheduled', 'appointment_type')
            ->selectRaw('(CASE WHEN STR_TO_DATE(date, "%m/%d/%Y") < "' . $d . '" THEN "past"
                                                WHEN STR_TO_DATE(date, "%m/%d/%Y") = "' . $d . '" AND TIME_FORMAT(STR_TO_DATE(start_time, "%h:%i %p"), "%H:%i:%s") < "' . $currentTime . '" AND TIME_FORMAT(STR_TO_DATE(end_time, "%h:%i %p"), "%H:%i:%s") <= "' . $currentTime . '" THEN "past"
                                                WHEN STR_TO_DATE(date, "%m/%d/%Y") = "' . $d . '" AND TIME_FORMAT(STR_TO_DATE(start_time, "%h:%i %p"), "%H:%i:%s") <= "' . $currentTime . '" AND TIME_FORMAT(STR_TO_DATE(end_time, "%h:%i %p"), "%H:%i:%s") >= "' . $currentTime . '" THEN "current"
                                                WHEN STR_TO_DATE(date, "%m/%d/%Y") = "' . $d . '" AND TIME_FORMAT(STR_TO_DATE(start_time, "%h:%i %p"), "%H:%i:%s") >= "' . $currentTime . '" AND TIME_FORMAT(STR_TO_DATE(end_time, "%h:%i %p"), "%H:%i:%s") >= "' . $currentTime . '" THEN "upcoming"
                                                WHEN STR_TO_DATE(date, "%m/%d/%Y") > "' . $d . '" THEN "upcoming"
                                                ELSE "2" END) as status')
            ->when($type == "past", function ($query) {
                $query->selectRaw('(select count(id) from prescriptions where appointment_id = appointments.id ) as is_completed');
            })
            ->when($type == 'upcoming', function ($upcoming) use ($currentDateTime, $currentTime) {
                return $upcoming->where(function ($upcoming) use ($currentDateTime, $currentTime) {
                    $upcoming->where('date', '=', $currentDateTime)
                        // ->whereRaw("TIME_FORMAT(STR_TO_DATE(end_time, '%h:%i %p'), '%H:%i:%s') <= ?", [$currentTime])
                        // ->orWhere('date', '<', $currentDateTime)
                    ;
                });
            })
            // ->where('doctor_id', auth()->id())
            // ->whereIsDeleted(false)
            ->groupBy('date', 'id')
            ->orderBy('date')
            ->get();

        foreach ($appointments as &$appointment) {
            // Parse UTC date and start time
            $date = $appointment->date . ' ' . $appointment->start_time;

            // Create a DateTime object with UTC timezone
            $utcDateTime = DateTime::createFromFormat('m/d/Y h:i A', $date, new DateTimeZone('UTC'));

            // Convert to 'Asia/Karachi' timezone
            $karachiTimeZone = new DateTimeZone('Asia/Karachi');
            $utcDateTime->setTimezone($karachiTimeZone);

            // Update the appointment array with converted date and start time
            $appointment['date'] = $utcDateTime->format('m/d/Y');
            $appointment['start_time'] = $utcDateTime->format('h:i A');
        }



        return $appointments;


        return Appointment::get();
        $stripe = new \Stripe\StripeClient('sk_test_51H0UoCJELxddsoRYdF40WwR8HUvA8U5wgUNqQwDCweZT4TnbAuIGINVtVWAItPMcSoMOighLxdZR1Jjl8vdUwldb00EMPAVgIE');
        // ch_3NrxSWJELxddsoRY0U1SmilH
        // return $stripe->refunds->create([
        //     'charge' => 'ch_3NrxSWJELxddsoRY0U1SmilH',
        //     'amount' => 10 * 100
        //   ]);



        return $stripe->customers->allBalanceTransactions(
            'cus_9s6XKzkNRiz8i3',
            ['limit' => 3]
        );

        return $stripe->charges->retrieve(
            'ch_3NrxSWJELxddsoRY0U1SmilH'
        );

        $customerId = "cus_OYW6VYA2LPEoIQ";
        // $cards = $paymentService->getAllCards($customerId);
        $cardId = "card_1NlP4kJELxddsoRYq7W4oaTS";
        $charge = $paymentService->chargeAmount($cardId, $customerId, 100);
        return  $charge;
        // return $cards;
        return "12";
    }

    public function laboratories(Request $request)
    {

        $name = $request->name;
        $location = $request->location;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $minDistance = $request->min_distance;
        $maxDistance = $request->max_distance;
        $experience_from = $request->experience_from;
        $experience_to = $request->experience_to;

        $pharmacies = User::with(['additional_data', 'schedule'])->whereRole('labortory')
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
            ->whereHas('additional_data', function ($query) use ($experience_from, $experience_to) {
                $query->when($experience_from && $experience_to, function ($query) use ($experience_from, $experience_to) {

                    $query->whereBetween('years_of_experience', [$experience_from, $experience_to]);
                });
            })
            ->whereProfileCompleted(true)
            ->whereRaw('id not  in (select report_user_id from reports where user_id = ' . auth()->id() . ')')
            ->get();
        return apiSuccessMessage("Laboratory", $pharmacies);
    }

    public function chatInbox()
    {
        $requests = PatientRequest::join('users', 'users.id', 'patient_requests.user_id')
            ->selectRaw('patient_requests.id as request_id ,users.id as user_id ,
                                        first_name, last_name, avatar, state, prescription_id,
                                        status,DATE_FORMAT(patient_requests.created_at, "%M %d, %Y") as date,
                                        DATE_FORMAT(expiration_date_time, "%m/%d/%Y") as expiry_date,
                                        TIME_FORMAT(expiration_date_time, "%H:%i") AS expiry_time')
            ->whereOtherUserId(auth()->id())
            ->where('status', '=', "accepted")
            ->orderByDesc('patient_requests.created_at')
            ->get();


        return apiSuccessMessage("Requests", $requests);
    }
    public function requests(Request $request)
    {
        $date = $request->date;
        $formattedDate =  $date ? Carbon::createFromFormat('m/d/Y', $request->date)->format('Y-m-d') : "";

        $name = $request->name;
        $requests = PatientRequest::join('users', 'users.id', 'patient_requests.user_id')
            ->selectRaw('users.id as user_id , patient_requests.id ,
                                        first_name, last_name, avatar, state, prescription_id,
                                        status,patient_requests.created_at')
            ->when($formattedDate, function ($query) use ($formattedDate) {
                $query->whereDate('patient_requests.created_at', $formattedDate);
            })
            ->when($name, function ($query) use ($name) {
                $query->where(function ($query) use ($name) {
                    $query->orWhereRaw('CONCAT(first_name, " ", last_name) LIKE ?', ["%$name%"]);
                });
            })
            ->whereOtherUserId(auth()->id())
            ->where('status', '!=', "rejected")
            ->orderByDesc('patient_requests.created_at')
            ->get();


        $requests = $requests->groupBy('created_at')
            ->map(function ($requests, $date) {
                $date = Carbon::parse($date);
                $today = $date->isToday() ? "Today," : "";
                $new_date = $today . " " . $date->isoFormat("LL");
                return [
                    'date' => $new_date,
                    'requests' => $requests->map(function ($requests) use ($new_date) {
                        return [
                            'request_id' => $requests?->id,
                            'user_id' => $requests?->user_id,
                            'first_name' => $requests?->first_name,
                            'last_name' => $requests?->last_name,
                            'avatar' => $requests?->avatar,
                            'state' => $requests?->state,
                            'prescription_id' => $requests?->prescription_id,
                            'status' => $requests?->status,
                            'date' => $new_date,
                        ];
                    }),
                ];
            })
            ->values();

        return apiSuccessMessage("Requests", $requests);
    }

    public function updateRequestStatus(Request $request)
    {
        $this->validate($request, [
            'request_id' => 'required|exists:patient_requests,id',
            'type' => 'required|in:accept,reject'
        ]);

        $request_id = $request->request_id;
        $type = $request->type;
        $reason = $request->reason;
        $other_reason = $request->other_reason;
        $request = PatientRequest::whereId($request_id)->first();

        if ($type == "accept" && $request->status == "accepted") {
            return commonErrorMessage("Already accepted", 400);
        }

        if ($type == "accept" && $request->status == "rejected") {
            return commonErrorMessage("Can't accept after rejecting", 400);
        }

        if ($type == "accept" && $request->status == "pending") {
            $currentDateTime = Carbon::now("UTC");

            // Add 72 hours (3 days) to the current date and time
            $futureDateTime = $currentDateTime->copy()->addHours(72);

            $request->status = "accepted";
            $request->acceptance_date_time = $currentDateTime;
            $request->expiration_date_time = $futureDateTime;
            $request->save();

            $message = auth()->user()->first_name . " has accepted your request.";
            $user = User::whereId($request->user_id)->first();
            $token = $user->device_token;
            $data = [
                'to_user_id'        =>  $user->id,
                'from_user_id'      =>  auth()->id(),
                'notification_type' =>  'REQUEST_ACCEPT',
                'title'             =>  $message,
                'description'        => $message,
                'redirection_id'    =>   $user->id
            ];


            app(CreateDBNotification::class)->execute($data);
            if ($token) {
                app(PushNotificationService::class)->execute($data, [$token]);
            }
            return commonSuccessMessage("Accepted Successfully");
        }

        if ($type == "reject" && $request->status == "rejected") {
            return commonErrorMessage("Already rejected", 400);
        }

        if ($type == "reject" && $request->status == "accepted") {
            return commonErrorMessage("Can't reject after accepting", 400);
        }

        if ($type == "reject" && $request->status == "pending") {
            $request->status = "rejected";
            $request->save();
            $request->reason = $reason;
            $request->other_reason = $other_reason;
            $request->save();
            return commonSuccessMessage("Rejected Successfully");
        }
    }

    public function requestDetail(Request $request)
    {

        $this->validate($request, [
            'request_id' => 'required|exists:patient_requests,id'
        ]);
        $detail = PatientRequest::with([
            'user:id,first_name,last_name,avatar,state',
            'prescription' => function ($prescription) {
                $prescription->selectRaw("id,doctor_id,fitness,medical_advice,lab_advice,prescription,DATE_FORMAT(created_at, '%M %d, %Y') as date");
            },
            'prescription.doctor' => function ($doctor) {
                $doctor->select('id', 'first_name', 'last_name')
                    ->selectRaw('(select specialty from doctor_profiles where user_id = users.id order by id desc LIMIT 1) as specialty');
            }
        ])
            ->selectRaw("DATE_FORMAT(created_at, '%M %d, %Y') as request_date,user_id,prescription_id,status")
            ->whereId($request->request_id)->first();
        return apiSuccessMessage("", $detail);
    }

    public function history()
    {
        $history = User::select('users.id', 'users.first_name', 'users.last_name', 'users.avatar', 'users.state', 'patient_requests.status')
            ->selectRaw(" DATE_FORMAT(patient_requests.created_at, '%M %d, %Y') as
                            date")
            ->leftJoin('patient_requests', function ($join) {
                $join->on('users.id', '=', 'patient_requests.user_id')
                    ->where('patient_requests.other_user_id',  auth()->id());
            })
            ->leftJoin('patient_requests as a2', function ($join) {
                $join->on('users.id', '=', 'a2.user_id')
                    ->where('a2.other_user_id',  auth()->id())
                    ->whereRaw('patient_requests.created_at < a2.created_at');
            })
            ->whereNull('a2.id')
            ->whereNotNull('patient_requests.created_at')
            ->whereIn('patient_requests.status', ['accepted', 'rejected'])
            ->groupBy('users.id')
            ->orderByRaw("(patient_requests.created_at) DESC")

            ->get();
        return apiSuccessMessage("History", $history);
    }

    public function historyDetail(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users,id'
        ]);
        $detail = PatientRequest::with([
            'prescription' => function ($prescription) {
                $prescription->selectRaw("id,doctor_id,fitness,medical_advice,lab_advice,prescription,DATE_FORMAT(created_at, '%M %d, %Y') as date");
            },
            'prescription.doctor' => function ($doctor) {
                $doctor->select('id', 'first_name', 'last_name')
                    ->selectRaw('(select specialty from doctor_profiles where user_id = users.id order by id desc LIMIT 1) as specialty');
            }
        ])
            ->selectRaw("DATE_FORMAT(created_at, '%M %d, %Y') as request_date,user_id,prescription_id,status")
            ->whereUserId($request->user_id)
            ->whereIn('status', ['accepted', 'rejected'])
            ->get();

        return apiSuccessMessage("Detail", $detail);
    }

    public function prescriptionRequests(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|in:labortory,pharmacy'
        ]);


        $requests = PatientRequest::join('users', 'users.id', 'patient_requests.other_user_id')
            ->selectRaw("users.id as user_id , patient_requests.id ,
                                        first_name as name, avatar, state, prescription_id,
                                        status,DATE_FORMAT(patient_requests.created_at, '%M %d, %Y') as date")
            ->whereUserId(auth()->id())
            ->whereType($request->type)
            ->where('status', '!=', "rejected")
            ->orderByDesc('patient_requests.created_at')
            ->get();





        return apiSuccessMessage("Requests", $requests);
    }
}
