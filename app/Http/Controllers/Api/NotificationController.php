<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Services\Notifications\CreateDBNotification;
use App\Services\Notifications\PushNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function preAppointmentAlert() {
        $currentDateTime = Carbon::today()->format('m/d/Y');
        $currentTime = Carbon::now()->format('H:i:s');
        
        $fifteenMinutesFromNow = Carbon::now()->addMinutes(15)->format('H:i:s');
       
        $appointments =  Appointment::where('date', $currentDateTime)
        ->whereRaw("TIME_FORMAT(STR_TO_DATE(start_time, '%h:%i %p'), '%H:%i:%s') BETWEEN ? AND ?", [$currentTime, $fifteenMinutesFromNow])
        ->whereNotificationSent(false)
        ->get();
        // return $appointments;
        $user_ids = $appointments->pluck('user_id')->concat($appointments->pluck('doctor_id'))->unique();
        $users = User::whereIn('id', $user_ids)->select('id','device_token')->get();
        foreach($appointments as $appointment) {
            $doctor_id = $appointment->doctor_id;
            $user_id = $appointment->user_id;
            $appointment_id = $appointment->id;
            $message = "Your appointment will start soon.";
            $doctor_data = [
                'to_user_id'        =>  $doctor_id,
                'from_user_id'      =>  "",
                'notification_type' =>  'APPOINTMENT_ALERT',
                'title'             =>  $message,
                'description'        => $message,
                'redirection_id'    =>   $appointment_id
            ];

            $user_data = [
                'to_user_id'        =>  $user_id,
                'from_user_id'      =>  "",
                'notification_type' =>  'APPOINTMENT_ALERT',
                'title'             =>  $message,
                'description'        => $message,
                'redirection_id'    =>   $appointment_id
            ];
    
            //Send Notification to Doctor 
            $doctor_token = $users->where('id', $doctor_id )->first()?->device_token;
            app(CreateDBNotification::class)->execute($doctor_data);
            if ($doctor_token) {
                app(PushNotificationService::class)->execute($doctor_data,[$doctor_token]);
            }

            //Send Notification to User 
            $user_token = $users->where('id', $user_id )->first()?->device_token;
            
            app(CreateDBNotification::class)->execute($user_data);
            if ($user_token) {
                app(PushNotificationService::class)->execute($doctor_data,[$user_token]);
            }

            $appointment->notification_sent = 1;
            $appointment->save();
        }
    }
}
