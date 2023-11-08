<?php

namespace App\Http\Controllers\Api;

use App\Events\AppointmentBookedEvent;
use App\Exceptions\AppException;
use App\Http\Controllers\Api\Doctor\DoctorController;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DisputeAppointment;
use App\Models\DoctorProfile;
use App\Models\HelpFeedback;
use App\Models\Image;
use App\Models\Invoice;
use App\Models\Prescription;
use App\Models\Report;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\User;
use App\Services\Messages\MessagesInboxService;
use App\Services\Notifications\CreateDBNotification;
use App\Services\Notifications\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PatientController extends Controller
{

    public  function chatInbox(Request $request, MessagesInboxService $inbox)
    {

        $inboxes = $inbox->pharmacyLaboratoryChat();
        return apiSuccessMessage(
            "Success",
            [
                'labortory' => $inboxes->where('type', 'labortory')->values(),
                'pharmacy' => $inboxes->where('type', 'pharmacy')->values(),
                'doctor' => $inbox->chatInboxContainingDoctor()
            ]
        );
    }

    public function getInvoices()
    {
        // Or, if you want a unique string
        $transactionId = Str::random(10);
        return $transactionId;
        $invoices = Invoice::with('doctor')->get();
        return $invoices;
    }

    public function getSlots(Request $request)
    {
        //mm-dd-yyyy

        $this->validate($request, [
            'date' => 'required|date',
            'doctor_id' => 'required',
        ]);

        $doctor_id = $request->doctor_id;
        $carbonDate = Carbon::createFromFormat('m/d/Y', $request->date);

        $dayName = $carbonDate->format('l');

        $schdule = Schedule::where('user_id', $doctor_id)->where('day', $dayName)->whereIsChecked(true)->first();

        if (!$schdule) {
            throw new AppException("Doctor has no any Schedule.");
        }

        $start_time =  $schdule?->start_time;
        $end_time = $schdule?->end_time;

        $pattern = '/^(00|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/';


        if (
            is_null($start_time) ||
            is_null($end_time)
        ) {
            throw new AppException("Doctor has no any Schedule.");
        }

        if (
            !preg_match($pattern, $start_time) ||
            !preg_match($pattern, $end_time)
        ) {

            throw new AppException("Something went wrong.");
        }






        $start_time = Carbon::createFromFormat('H:i', $start_time);
        $end_time = Carbon::createFromFormat('H:i', $end_time);

        $start = $this->roundUpToNearest15($start_time, false);
        $end = $this->roundUpToNearest15($end_time, true);
        $slots = [];
        $bookedSlots = Appointment::where('date', $request->date)
            ->where('doctor_id', $doctor_id)
            ->where('is_cancelled', 0)
            ->get(['start_time', 'end_time']);

        while ($start->lt($end)) {
            $nextHour = $start->copy()->addMinutes(15);
            if ($nextHour->lte($end)) {
                $slot = [
                    'start_time' => $start->format('h:i A'),
                    'end_time' => $nextHour->format('h:i A'),
                ];

                // Convert slot times to a common format for comparison
                $slotStart = $start->format('H:i');
                $slotEnd = $nextHour->format('H:i');

                // Check if the slot is not in the booked slots
                $matchingBookedSlots = $bookedSlots->filter(function ($value, $key) use ($slotStart, $slotEnd) {
                    $startTime = Carbon::createFromFormat('h:i A', $value['start_time'])->format('H:i');
                    $endTime = Carbon::createFromFormat('h:i A', $value['end_time'])->format('H:i');
                    return $startTime === $slotStart && $endTime === $slotEnd;
                });

                if ($matchingBookedSlots->isEmpty()) {

                    $slots[] = $slot;
                } else {
                    // Remove the matched booked slots
                    $bookedSlots = $bookedSlots->reject(function ($value, $key) use ($slotStart, $slotEnd) {
                        $startTime = Carbon::createFromFormat('h:i A', $value['start_time'])->format('H:i');
                        $endTime = Carbon::createFromFormat('h:i A', $value['end_time'])->format('H:i');
                        return $startTime === $slotStart && $endTime === $slotEnd;
                    });
                }
            }

            $start->addMinutes(15);
        }



        // foreach ($slots as &$slot) {
        //     foreach ($bookedSlots as $bookedSlot) {
        //         if ($slot['start_time'] == $bookedSlot['start_time'] && $slot['end_time'] == $bookedSlot['end_time']) {
        //             $slot['booked'] = true;
        //             break;
        //         }
        //     }
        // }

        // (array)$slots;
        return apiSuccessMessage("Success", collect($slots));
    }

    function roundUpToNearest15($time, $isEndTime = false)
    {
        $minutes = $time->minute;
        $remainder = $minutes % 15;

        if ($isEndTime) {
            // If it's the end time, round down to the nearest 15 minutes
            return $time->subMinutes($remainder);
        }

        if ($remainder === 0) {
            return $time;
        }

        return $time->addMinutes(15 - $remainder);
    }


    public function getDoctors(Request $request)
    {
        $name = $request->name;
        $speciality = $request->speciality;
        $fee_from = $request->fee_from;
        $fee_to = $request->fee_to;
        $language = $request->language;
        $experience_from = $request->experience_from;
        $experience_to = $request->experience_to;
        $latitude = $request->latitude;
        $longitude = $request->longitude;
        $range = $request->has('latitude') && $request->has('longitude') ? 5 : 0;
        $doctors = User::with(['doctor_profile' => function ($query) use ($speciality) {
            $query->where('specialty', 'LIKE', "%$speciality%");
        }])
            ->select(
                'id',
                'first_name',
                'last_name',
                'email',
                'avatar',
                'address',
                'city',
                'state',
                'zip_code',
                'language',
                'gender',
            )
            ->when($latitude &&  $longitude, function ($lat_and_lang) use ($latitude, $longitude) {
                $lat_and_lang->selectRaw('( 6371 * acos( cos( radians(?) ) *
                                cos( radians( latitude ) )
                                * cos( radians( longitude ) - radians(?)
                                ) + sin( radians(?) ) *
                                sin( radians( latitude ) ) )
                            ) AS distance', [$latitude, $longitude, $latitude]);
            })
            ->when($range > 0 && $latitude &&  $longitude, function ($q) use ($range) {
                $q->having('distance', '<=', $range);
            })
            ->selectRaw(' (select CASE WHEN count(id) > 0 THEN 1 ELSE 0 END from
                                        images where images.table_id = users.id AND table_name = "users" AND
                                        image_type ="certificates" ) as is_certified,
                            (SELECT AVG (rating) from reviews where rating_user_id = users.id) as avg_rating ,
                            "1.3K" as successfull_patients
                        ')
            ->where('role', 'doctor')
            ->where('profile_completed', '1')
            ->when($name, function ($query) use ($name) {
                $query->where(function ($query) use ($name) {
                    $query->orWhereRaw('CONCAT(first_name, " ", last_name) LIKE ?', ["%$name%"]);
                });
            })
            ->when($language, function ($query) use ($language) {
                $query->where('language', 'LIKE', "%$language%");
            })
            ->whereHas('doctor_profile', function ($query) use ($speciality, $fee_from, $fee_to, $experience_from, $experience_to) {
                $query->when($speciality, function ($query) use ($speciality) {
                    $query->where('specialty', 'LIKE', "%$speciality%");
                });

                $query->when($fee_from && $fee_to, function ($query) use ($fee_from, $fee_to) {
                    $query->whereBetween('consultation_fee', [$fee_from, $fee_to]);
                });

                $query->when($experience_from && $experience_to, function ($query) use ($experience_from, $experience_to) {
                    $query->whereBetween('year_of_experience', [$experience_from, $experience_to]);
                });
            })
            ->whereRaw('id not  in (select report_user_id from reports where user_id = ' . auth()->id() . ')')
            ->get();
        return apiSuccessMessage("Doctors", $doctors);
    }

    public function  bookAppointment(Request $request)
    {
        $this->validate($request, [
            'doctor_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $doctor = User::find($value);
                    if (!$doctor || $doctor->role !== 'doctor') {
                        $fail('The selected doctor is not valid.');
                    }
                }
            ],
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'appointment_type' => 'required',
            'note' => 'required',
        ]);

        // return $doctor;
        $start_time =  $request->start_time;
        $end_time =  $request->end_time;
        $doctor_id = $request->doctor_id;
        $is_available =  $this->checkSlotIsAvailable($doctor_id, $request->date, $start_time, $end_time);

        if (!$is_available) {
            return commonErrorMessage("Given Slot is invalid.", 400);
        }

        // $date = Carbon::createFromFormat('m/d/Y', $request->date)->format('Y-m-d');
        $appointments =  Appointment::where('date', $request->date)
            ->where(['start_time' => $start_time, 'end_time' => $end_time])
            ->exists();
        if ($appointments) {
            return commonErrorMessage("This slot has been booked", 400);
        }

        $doctor = User::whereId($doctor_id)->first();
        $fee = DoctorProfile::where('user_id', $request->doctor_id)->first()?->consultation_fee;

        if (!$fee) {
            return commonErrorMessage("Something went wrong.", 400);
        }

        $appointment = Appointment::create([
            'user_id' => auth()->id(),
            'doctor_id' => $doctor_id,
            'date' => $request->date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'appointment_type' => $request->appointment_type,
            'note' => $request->note,
            'fee' => $fee,
        ]);
        event(new AppointmentBookedEvent(auth()->id(), $doctor->id, $doctor->device_token, auth()->user()->first_name . " " . auth()->user()->last_name . " has booked an appointment."));

        generateInvoice($doctor_id, auth()->id(), $appointment->id, $fee, "Appointment Book");

        return commonSuccessMessage("Success");
    }


    public function rescheduleAppointment(Request $request)
    {
        $this->validate($request, [
            'appointment_id' => 'required|exists:appointments,id',
            'doctor_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'appointment_type' => 'required',
            'note' => 'required',
            'reason' => 'required',
        ]);

        $start_time =  $request->start_time;
        $end_time =  $request->end_time;
        $doctor_id = $request->doctor_id;
        $is_available =  $this->checkSlotIsAvailable($doctor_id, $request->date, $start_time, $end_time);

        if (!$is_available) {
            return commonErrorMessage("Given Slot is invalid.", 400);
        }

        $appointment =  Appointment::where('id', $request->appointment_id)
            ->first();

        if ($appointment->user_id != auth()->id()) {
            return commonErrorMessage("Can not reschedule appointmnet", 400);
        }
        $differenceInHours = $this->checkDifferenceInHours($appointment->date, $appointment->start_time);

        if ($differenceInHours < 72) {
            return commonErrorMessage("Appointment must be rescheduled before 72 hours of appointment start.", 400);
        }
        if (
            $appointment->date == $request->date &&
            $appointment->start_time == $request->start_time &&
            $appointment->end_time == $request->end_time
        ) {
            return commonErrorMessage("Can not reschedule for the same time", 400);
        }

        $fee = calculatePercentage($appointment->fee, 10);
        Appointment::create([
            'user_id' => auth()->id(),
            'doctor_id' => $request->doctor_id,
            'date' => $request->date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'appointment_type' => $request->appointment_type,
            'appointment_id' => $appointment->id,
            'note' => $request->note,
            'fee' => $fee,
            'reason' => $request->reason,
            'note' => $request->note,
            'previous_appointment' => json_encode($appointment),
            'is_resheduled' => 1,
        ]);

        $message = auth()->user()->first_name . " " . auth()->user()->last_name . " has rescheduled an appointment.";
        $doctor = User::whereId($appointment->doctor_id)->first();
        $token = $doctor->device_token;
        $data = [
            'to_user_id'        =>  $doctor->id,
            'from_user_id'      =>  auth()->id(),
            'notification_type' =>  'CANCEL_APPOINTMENT',
            'title'             =>  $message,
            'description'        => $message,
            'redirection_id'    =>   $doctor->id
        ];


        app(CreateDBNotification::class)->execute($data);
        if ($token) {
            app(PushNotificationService::class)->execute($data, [$token]);
        }
        generateInvoice($doctor_id, auth()->id(), $appointment->id, $fee, "Reschedule Appointment");

        $appointment->is_deleted = 1;
        $appointment->save();

        return commonSuccessMessage("Appointment Rescheduled");
    }

    public function cancelAppointment(Request $request)
    {
        $this->validate($request, [
            'appointment_id' => 'required|exists:appointments,id'
        ]);

        $appointment = Appointment::whereId($request->appointment_id)->first();
        if ($appointment->user_id != auth()->id()) {
            return commonErrorMessage("Can not cancel other appoinment.", 400);
        }

        if ($appointment->is_cancelled == 1) {
            return commonErrorMessage("Appointment already cancelled.", 400);
        }
        $differenceInHours = $this->checkDifferenceInHours($appointment->date, $appointment->start_time);

        if ($differenceInHours < 72) {
            return commonErrorMessage("Appointment must be cancel before 72 hours of appointment start.", 400);
        }

        $appointment->is_cancelled = 1;
        $appointment->save();

        $message = auth()->user()->first_name . " " . auth()->user()->last_name . " has cancelled an appointment.";
        $doctor = User::whereId($appointment->doctor_id)->first();
        $token = $doctor->device_token;
        $data = [
            'to_user_id'        =>  $doctor->id,
            'from_user_id'      =>  auth()->id(),
            'notification_type' =>  'CANCEL_APPOINTMENT',
            'title'             =>  $message,
            'description'        => $message,
            'redirection_id'    =>   $doctor->id
        ];


        app(CreateDBNotification::class)->execute($data);
        if ($token) {
            app(PushNotificationService::class)->execute($data, [$token]);
        }

        $fee =  calculatePercentage($appointment->fee, 75);
        generateInvoice($appointment->doctor_id, auth()->id(), $appointment->id, $fee, "Cancel Appointment");

        return commonSuccessMessage("Appionment cancelled.");
    }

    public function getAppointmetns(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|in:current,past'
        ]);

        $type = $request->type;


        $currentDateTime = Carbon::now()->format('m/d/Y');
        $currentTime = Carbon::now()->format('H:i:s');

        $appointments = Appointment::with('doctor')
            ->select('id', 'doctor_id', 'date', 'start_time', 'end_time', 'is_resheduled')
            ->selectRaw("CASE
                                                WHEN date = CURDATE() THEN 'Today'
                                                ELSE DATE_FORMAT(STR_TO_DATE(date, '%m/%d/%Y'), '%M %d, %Y')
                                            END AS date")
            ->where(function ($query) use ($type, $currentDateTime, $currentTime) {
                $query->when($type == "current", function ($query) use ($currentDateTime, $currentTime) {
                    $query->where(function ($query) use ($currentDateTime, $currentTime) {
                        $query->where('date', '=', $currentDateTime)
                            ->whereRaw("TIME_FORMAT(STR_TO_DATE(start_time, '%h:%i %p'), '%H:%i:%s') >= ?", [$currentTime]);
                    })->orWhere('date', '>', $currentDateTime);
                })
                    ->when($type == "past", function ($query) use ($currentDateTime, $currentTime) {
                        $query->where(function ($query) use ($currentDateTime, $currentTime) {
                            $query->where('date', '=', $currentDateTime)
                                ->whereRaw("TIME_FORMAT(STR_TO_DATE(start_time, '%h:%i %p'), '%H:%i:%s') < ?", [$currentTime]);
                        })->orWhere('date', '<', $currentDateTime);
                    });
            })
            ->where('user_id', auth()->id())
            ->whereIsCancelled(false)
            ->whereIsDeleted(false)
            ->get();
        return apiSuccessMessage("Data", $appointments);
    }

    public function history()
    {
        $currentDateTime = Carbon::now()->format('m/d/Y');
        $currentTime = Carbon::now()->format('H:i:s');
        // return $currentTime;
        $usersWithLatestAppointments = User::select('users.id', 'users.first_name', 'users.last_name', 'users.avatar')
            ->selectRaw("CASE
                                                WHEN appointments.date = CURDATE() THEN 'Today'
                                                ELSE DATE_FORMAT(STR_TO_DATE(appointments.date, '%m/%d/%Y'), '%M %d, %Y')
                                            END AS date, (select specialty from doctor_profiles where user_id = users.id LIMIT 1) as specialty")
            ->leftJoin('appointments', function ($join) {
                $join->on('users.id', '=', 'appointments.doctor_id')
                    ->where('appointments.user_id', auth()->id());
            })
            ->leftJoin('appointments as a2', function ($join) {
                $join->on('users.id', '=', 'a2.doctor_id')
                    ->where('a2.user_id', auth()->id())
                    ->whereRaw('appointments.date < a2.date');
            })
            ->whereNull('a2.id')
            ->whereNotNull('appointments.date')
            ->where(function ($query) use ($currentDateTime, $currentTime) {
                $query->where(function ($query) use ($currentDateTime, $currentTime) {
                    $query->where('appointments.date', '=', $currentDateTime)
                        ->whereRaw("TIME_FORMAT(STR_TO_DATE(appointments.end_time, '%h:%i %p'), '%H:%i:%s') < ?", [$currentTime]);
                })->orWhere('appointments.date', '<', $currentDateTime);
            })
            ->orderByRaw("STR_TO_DATE(appointments.date, '%m/%d/%Y') DESC")
            ->get();

        return apiSuccessMessage("Success", $usersWithLatestAppointments);
    }

    public function historyDetail(Request $request)
    {
        $this->validate($request, [
            'doctor_id' => 'required|exists:users,id'
        ]);
        $doctor_id = $request->doctor_id;
        $currentDateTime = Carbon::today()->format('m/d/Y');
        $currentTime = Carbon::now()->format('H:i:s');

        $doctor = User::with('doctor_profile')
            ->select(
                'id',
                'first_name',
                'last_name',
                'email',
                'avatar',
                'address',
                'city',
                'state',
                'zip_code',
                'language',
                'gender',
            )
            ->selectRaw(' (select CASE WHEN count(id) > 0 THEN 1 ELSE 0 END from
                                        images where images.table_id = users.id AND table_name = "users" AND
                                        image_type ="certificates" ) as is_certified,
                            (SELECT AVG (rating) from reviews where rating_user_id = users.id) as avg_rating ,
                            "1.3K" as successfull_patients
                        ')
            ->whereId($doctor_id)
            ->first();
        $appointments = Appointment::select('id', 'start_time', 'end_time', 'appointment_type', 'note', 'fee', 'doctor_id', 'date')
            ->selectRaw("CASE
                                                WHEN appointments.date = CURDATE() THEN 'Today'
                                                ELSE DATE_FORMAT(STR_TO_DATE(appointments.date, '%m/%d/%Y'), '%M %d, %Y')
                                            END AS date")
            ->where(function ($query) use ($currentDateTime, $currentTime) {
                $query->where(function ($query) use ($currentDateTime, $currentTime) {
                    $query->where('appointments.date', '=', $currentDateTime)
                        ->whereRaw("TIME_FORMAT(STR_TO_DATE(appointments.end_time, '%h:%i %p'), '%H:%i:%s') < ?", [$currentTime]);
                })->orWhere('appointments.date', '<', $currentDateTime);
            })
            ->whereDoctorId($doctor_id)
            ->whereUserId(auth()->id())
            ->whereIsDeleted(false)
            ->get();

        return apiSuccessMessage("Success", ['doctor' => $doctor, 'appointments' => $appointments]);
    }

    public function getprescriptions(Request $request)
    {
        $this->validate($request, [
            'type' => 'in:pharmacy,labortory',
            'pharmacy_id' => 'required_if:type,==,pharmacy',
            'labortory_id' => 'required_if:type,==,labortory',
        ]);

        $type = $request->type;

        $id = $request->has('pharmacy_id') ? $request->pharmacy_id : $request->labortory_id;
        $prescriptions = Prescription::with(['doctor' => function ($doctor) {
            $doctor->select('id', 'first_name', 'last_name', 'avatar', 'email')
                ->selectRaw('(select specialty from doctor_profiles where user_id = users.id order by id desc LIMIT 1) as specialty');
        }])
            ->selectRaw("id,prescription,doctor_id,DATE_FORMAT(created_at, '%M %d, %Y') as date")
            ->when($request->has('type'), function ($pharmacy) use ($id) {
                $pharmacy->selectRaw("(select (CASE WHEN(status IS Null) THEN ''
                                                            WHEN(status = 'pending') THEN 'pending'
                                                            WHEN(status = 'rejected') THEN 'rejected'
                                                            WHEN(status = 'accepted' AND expiration_date_time > NOW() ) THEN 'accepted'
                                                            WHEN(status = 'accepted' AND expiration_date_time < NOW() ) THEN 'expired'

                                                            END) as status from patient_requests where user_id = prescriptions.user_id
                                AND other_user_id = $id order by id desc LIMIT 1) as status");
            })
            ->whereUserId(auth()->id())
            ->get();

        return apiSuccessMessage("Prescriptions", $prescriptions);
    }

    public function prescriptionDetail(Request $request)
    {
        $this->validate($request, [
            'prescription_id' => 'required|exists:prescriptions,id'
        ]);

        $prescription = Prescription::with(['doctor' => function ($doctor) {
            $doctor->select('id', 'first_name', 'last_name', 'avatar', 'email', 'address', 'city', 'state', 'zip_code', 'language', 'gender')
                ->selectRaw(' (select CASE WHEN count(id) > 0 THEN 1 ELSE 0 END from
                                        images where images.table_id = users.id AND table_name = "users" AND
                                        image_type ="certificates" ) as is_certified,
                            (SELECT AVG (rating) from reviews where rating_user_id = users.id) as avg_rating ,
                            "1.3K" as successfull_patients
                        ');
        }, 'doctor.doctor_profile'])->whereId($request->prescription_id)->first();
        return apiSuccessMessage("Detail", $prescription);
    }

    public function checkDifferenceInHours($date, $time)
    {
        $dateTimeString = $date . ' ' . $time;
        $dateTime = Carbon::createFromFormat('m/d/Y h:i A', $dateTimeString);

        // Get the current time
        $currentDateTime = Carbon::now();

        // Calculate the difference in hours
        $hoursDifference = $currentDateTime->diffInHours($dateTime);

        $sign = ($currentDateTime > $dateTime) ? '-' : '+';
        return $sign . $hoursDifference;
    }

    public function addReview(Request $request)
    {
        $this->validate($request, [
            'rating' => 'required|numeric|between:1,5',
            'rating_user_id' => 'required',
            'review' => 'required'
        ]);

        // ;
        Review::create($request->only(['rating', 'rating_user_id', 'review']) + ['user_id' => auth()->id()]);

        return commonSuccessMessage("Succcess");
    }

    public function getReviews(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required',
        ]);


        $reviews = Review::with('user:id,first_name,last_name,avatar,email,address')
            ->where('rating_user_id', $request->user_id)
            ->get(['id', 'user_id', 'rating', 'review', 'created_at']);

        return apiSuccessMessage("Success", $reviews);
    }


    public function report(Request $request)
    {
        $this->validate($request, [
            'report_user_id' => 'required|exists:users,id',
            // 'reason' => 'required',
            // 'other_reason' => 'required',
        ]);
        $report_user_id = $request->report_user_id;
        if (auth()->id() == $report_user_id) {
            return commonErrorMessage("Can't report yourself", 400);
        }

        $data = [
            'user_id' => auth()->id(),
            'report_user_id' => $report_user_id,
        ];
        // return $data + $request->only(['reason','other_reason']);
        $is_reported = Report::where($data)->exists();
        if ($is_reported) {
            return commonErrorMessage("Already reported", 400);
        }

        Report::create($data + $request->only(['reason', 'other_reason']));
        return commonSuccessMessage("Reported successfully.");
    }

    public function disputeAppointment(Request $request)
    {
        $this->validate($request, [
            'appointment_id' => 'required|exists:appointments,id',
            'reason' => 'required'
        ]);


        $appointment = Appointment::whereId($request->appointment_id)->first();

        if ($appointment->user_id != auth()->id()) {
            return commonErrorMessage("Can not dipute appointment", 400);
        }

        $differenceInHours = $this->checkDifferenceInHours($appointment->date, $appointment->start_time);
        if ($differenceInHours > 0) {
            return commonErrorMessage("Can not dispute  before appointment start time", 400);
        }

        if ($differenceInHours < -72) {
            return commonErrorMessage("Can not dispute  after 72 hours", 400);
        }
        return $differenceInHours;


        $dispute = DisputeAppointment::create([
            'user_id' => $appointment->user_id,
            'doctor_id' => $appointment->doctor_id,
            'appointment_id' => $appointment->id,
            'appointment_id' => $appointment->id,
        ]);
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as  $file) {
                $profile_image = $file->store('public/images');
                $path = Storage::url($profile_image);

                Image::create([
                    'table_id' => $appointment->id,
                    'table_name' => 'dispute_appointments',
                    'image_type' => 'dispute',
                    'image_path' => $path,
                ]);
            }
        }

        return "d";
    }

    public function checkSlotIsAvailable($doctor_id, $date, $user_start_time, $user_end_time)
    {
        $carbonDate = Carbon::createFromFormat('m/d/Y', $date);

        $dayName = $carbonDate->format('l');

        $schdule = Schedule::where('user_id', $doctor_id)->where('day', $dayName)->first();

        if (!$schdule) {
            throw new AppException("Doctor has no any Schedule.");
        }

        $start_time =  $schdule?->start_time;
        $end_time = $schdule?->end_time;

        $pattern = '/^(00|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/';


        if (
            is_null($start_time) ||
            is_null($end_time)
        ) {
            throw new AppException("Doctor has no any Schedule.");
        }

        if (
            !preg_match($pattern, $start_time) ||
            !preg_match($pattern, $end_time)
        ) {

            throw new AppException("Something went wrong.");
        }


        $start_time = Carbon::createFromFormat('H:i', $start_time);

        $end_time = Carbon::createFromFormat('H:i', $end_time);

        $start = $this->roundUpToNearest15($start_time, false);
        $end = $this->roundUpToNearest15($end_time, true);
        $slots = [];

        while ($start->lt($end)) {
            $nextTime = $start->copy()->addMinutes(15);

            if ($nextTime->lte($end)) {
                $slot = [
                    'start_time' => $start->format('h:i A'),
                    'end_time' => $nextTime->format('h:i A'),
                    'booked' => false
                ];

                $slots[] = $slot;
            }

            $start->addMinutes(15);
        }

        return collect($slots)->contains(function ($slot) use ($user_start_time, $user_end_time) {
            return $slot['start_time'] === $user_start_time &&
                $slot['end_time'] === $user_end_time;
        });
    }


    public function feedback(Request $request)
    {
        $this->validate($request, [
            'subject' => 'required',
            'message' => 'required',
        ]);

        DB::beginTransaction();
        try {
            //code...
            $feedback = HelpFeedback::create($request->only(['subject', 'message']) + ['user_id' => auth()->id()]);
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as  $file) {
                    $profile_image = $file->store('public/images');
                    $path = Storage::url($profile_image);

                    Image::create([
                        'table_id' => $feedback->id,
                        'table_name' => 'help_feedback',
                        'image_type' => 'feeback',
                        'image_path' => $path,
                    ]);
                }
            }

            DB::commit();

            return commonSuccessMessage("Feedback added successfully.");
        } catch (\Throwable $th) {
            DB::rollback();
            return commonErrorMessage("Something went wrong", 400);
            //throw $th;
        }
    }
}
