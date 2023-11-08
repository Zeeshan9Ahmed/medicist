<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\PastAndCurrentHealthConditionWithPrescriptionResource;
use App\Models\Appointment;
use App\Models\HealthCondition;
use App\Models\Prescription;
use App\Models\User;
use App\Services\Messages\MessagesInboxService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    public function chatInbox(MessagesInboxService $service)
    {
        $inbox = $service->chatInboxContainingUser();
        return apiSuccessMessage("Success", $inbox);
    }

    public function getAppointmetns(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|in:upcoming,past,date_wise',
            'date' => 'required_if:type,=,date_wise',
        ]);

        $type = $request->type;
        $currentDateTime = Carbon::now()->format('m/d/Y');
        $date = $request->date;
        $currentTime = Carbon::now()->format('H:i:s');

        $appointments = Appointment::with('user')
            ->select('id', 'user_id', 'date', 'start_time', 'end_time', 'is_resheduled', 'appointment_type')
            ->selectRaw('(CASE WHEN STR_TO_DATE(date, "%m/%d/%Y") < CURDATE() THEN "past"
                                                WHEN STR_TO_DATE(date, "%m/%d/%Y") = CURDATE() AND TIME_FORMAT(STR_TO_DATE(start_time, "%h:%i %p"), "%H:%i:%s") < "' . $currentTime . '" AND TIME_FORMAT(STR_TO_DATE(end_time, "%h:%i %p"), "%H:%i:%s") <= "' . $currentTime . '" THEN "past"
                                                WHEN STR_TO_DATE(date, "%m/%d/%Y") = CURDATE() AND TIME_FORMAT(STR_TO_DATE(start_time, "%h:%i %p"), "%H:%i:%s") <= "' . $currentTime . '" AND TIME_FORMAT(STR_TO_DATE(end_time, "%h:%i %p"), "%H:%i:%s") >= "' . $currentTime . '" THEN "current"
                                                WHEN STR_TO_DATE(date, "%m/%d/%Y") = CURDATE() AND TIME_FORMAT(STR_TO_DATE(start_time, "%h:%i %p"), "%H:%i:%s") >= "' . $currentTime . '" AND TIME_FORMAT(STR_TO_DATE(end_time, "%h:%i %p"), "%H:%i:%s") >= "' . $currentTime . '" THEN "upcoming"
                                                WHEN STR_TO_DATE(date, "%m/%d/%Y") > CURDATE() THEN "upcoming"
                                                ELSE "2" END) as status')
            ->when($type == "past", function ($query) {
                $query->selectRaw('(select count(id) from prescriptions where appointment_id = appointments.id ) as is_completed');
            })
            ->when($type == 'date_wise', function ($date_wise) use ($date) {
                return $date_wise->where('date', '=', $date);
            })
            ->when($type == 'upcoming', function ($upcoming) use ($currentDateTime, $currentTime) {
                return $upcoming->where('date', '=', $currentDateTime)
                    ->whereRaw("TIME_FORMAT(STR_TO_DATE(end_time, '%h:%i %p'), '%H:%i:%s') >= ?", [$currentTime])
                    ->orWhere('date', '>', $currentDateTime);
            })
            ->when($type == 'past', function ($upcoming) use ($currentDateTime, $currentTime) {
                return $upcoming->where(function ($upcoming) use ($currentDateTime, $currentTime) {
                    $upcoming->where('date', '=', $currentDateTime)
                        ->whereRaw("TIME_FORMAT(STR_TO_DATE(end_time, '%h:%i %p'), '%H:%i:%s') <= ?", [$currentTime])
                        ->orWhere('date', '<', $currentDateTime);
                });
            })
            ->where('doctor_id', auth()->id())
            ->whereIsDeleted(false)
            ->groupBy('date', 'id')
            ->orderBy('date')
            ->get();
        // return $appointments;
        $appointments = $appointments->groupBy('date')
            ->map(function ($appointments, $date) {
                $date = Carbon::createFromFormat('m/d/Y', $date);
                $new_date = $date->isToday() ? "Today" : $date->isoFormat("dddd, LL");
                return [
                    'date' => $new_date,
                    'appointment' => $appointments
                        ->map(function ($appointment) use ($new_date) {
                            $mappedAppointment = [
                                'id' => $appointment->id,
                                'date' => $new_date,
                                'status' => $appointment->status,
                                'start_time' => $appointment->start_time,
                                'end_time' => $appointment->end_time,
                                'is_rescheduled' => $appointment->is_resheduled,
                            ];

                            // Check if 'is_completed' is not null, then add it to the mapped data
                            if ($appointment->is_completed !== null) {
                                $mappedAppointment['is_completed'] = $appointment->is_completed ? 1 : 0;
                            }

                            $mappedAppointment['user'] = $appointment->user;

                            return $mappedAppointment;
                        }),
                ];
            })
            ->values();

        return apiSuccessMessage("Data", $appointments);
    }

    public function addPrescription(Request $request)
    {
        $this->validate($request, [
            'appointment_id' => 'required|exists:appointments,id',
            'fitness' => 'required',
            'medical_advice' => 'required',
            'lab_advice' => 'required',
            'prescription' => 'required',
        ]);


        $appointment = Appointment::whereId($request->appointment_id)->first();

        $data = [
            'user_id' => $appointment->user_id,
            'doctor_id' => auth()->id(),
            'appointment_id' => $appointment->id,
            'fitness' => $request->fitness,
            'medical_advice' => $request->medical_advice,
            'lab_advice' => $request->lab_advice,
            'prescription' => $request->prescription,
        ];

        Prescription::create($data);
        return commonSuccessMessage("Prescription added successfully");
        return $appointment;
    }

    public function viewHealthCondition(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users,id'
        ]);

        $healthCondtion = HealthCondition::where('user_id', $request->user_id)->get();
        $data =  new PastAndCurrentHealthConditionWithPrescriptionResource($healthCondtion);
        return apiSuccessMessage("", $data);
    }

    public function appoinmentDetail(Request $request)
    {
        $this->validate($request, [
            'appointment_id' => 'required|exists:appointments,id',
            'type' => 'required|in:confirm,completed',
        ]);

        $appointment = "";
        $type = $request->type;
        if ($type == "confirm") {
            $appointment = Appointment::with('user')
                ->select('id', 'start_time', 'end_time', 'appointment_type', 'note', 'fee', 'user_id', 'date')
                ->whereId($request->appointment_id)
                ->first();
        }

        if ($type == "completed") {
            $appointment = Appointment::with('user', 'prescription:id,fitness,medical_advice,lab_advice,prescription,appointment_id')
                ->select('id', 'start_time', 'end_time', 'appointment_type', 'note', 'fee', 'user_id', 'date')
                ->whereId($request->appointment_id)
                ->first();
        }

        $appointment->date = \Carbon\Carbon::createFromFormat('m/d/Y', $appointment->date)->format('F d, Y');
        return apiSuccessMessage("Success", $appointment);
    }

    public function history()
    {
        $currentDateTime = Carbon::today()->format('m/d/Y');
        $currentTime = Carbon::now()->format('H:i:s');

        $usersWithLatestAppointments = User::select('users.id', 'users.first_name', 'users.last_name', 'users.avatar', 'users.state')
            ->selectRaw("CASE
                                                WHEN appointments.date = CURDATE() THEN 'Today'
                                                ELSE DATE_FORMAT(STR_TO_DATE(appointments.date, '%m/%d/%Y'), '%M %d, %Y')
                                            END AS date")
            ->leftJoin('appointments', function ($join) {
                $join->on('users.id', '=', 'appointments.user_id')
                    ->where('appointments.doctor_id', auth()->id());
            })
            ->leftJoin('appointments as a2', function ($join) {
                $join->on('users.id', '=', 'a2.user_id')
                    ->where('a2.doctor_id', auth()->id())
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
            'user_id' => 'required|exists:users,id'
        ]);
        $currentDateTime = Carbon::today()->format('m/d/Y');
        $currentTime = Carbon::now()->format('H:i:s');
        $user_id = $request->user_id;
        $user = User::whereId($user_id)->select('id', 'first_name', 'last_name', 'avatar', 'state')->first();
        $appointments = Appointment::with('prescription:id,fitness,medical_advice,lab_advice,prescription,appointment_id')
            ->select('id', 'start_time', 'end_time', 'appointment_type', 'note', 'fee', 'user_id', 'date')
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
            ->whereUserId($request->user_id)
            ->whereDoctorId(auth()->id())
            ->get();

        return apiSuccessMessage("Success", ['user' => $user, 'appointments' => $appointments]);
    }

    public function patientProfile(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users,id'
        ]);

        $user = User::with('emergency_contacts')->select(
            'id',
            'first_name',
            'last_name',
            'avatar',
            'email',
            'address',
            'gender',
            'weight',
            'weight_type',
            'height_feet',
            'height_inch',
            'phone_number'
        )
            ->whereId($request->user_id)
            ->first();

        return apiSuccessMessage("Success", $user);
    }
}
