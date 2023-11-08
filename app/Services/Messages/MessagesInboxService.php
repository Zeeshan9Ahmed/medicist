<?php


namespace App\Services\Messages;

use App\Exceptions\AppException;
use App\Models\Appointment;
use App\Models\PatientRequest;
use App\Models\User;
use Exception;

class MessagesInboxService
{


  public function pharmacyLaboratoryChat()
  {
    $requests = PatientRequest::join('users', 'users.id', 'patient_requests.other_user_id')
      ->selectRaw('patient_requests.id as request_id ,users.id as user_id , 
                                        first_name as name,users.avatar ,prescription_id,
                                        status,DATE_FORMAT(patient_requests.created_at, "%M %d, %Y") as date,
                                        DATE_FORMAT(expiration_date_time, "%m/%d/%Y") as expiry_date,
                                        TIME_FORMAT(expiration_date_time, "%H:%i") AS expiry_time,type')
      ->whereUserId(auth()->id())
      ->where('status', '=', "accepted")
      ->orderByDesc('patient_requests.created_at')
      ->get();
    return $requests;
  }

  public function chatInboxContainingDoctor()
  {
      $appointments = Appointment::with('doctor')->select('id','doctor_id','note','date','start_time','end_time')
                              ->selectRaw('DATE_FORMAT(created_at, "%M %d, %Y") as booking_date')
                              ->whereUserId(auth()->id())
                              ->whereIsCancelled(false)
                              ->whereIsDeleted(false)
                              ->get();
      return $appointments;
    
  }

  public function chatInboxContainingUser()
  {
      $appointments = Appointment::with('user')->select('id','doctor_id','note','date','start_time','end_time','user_id')
                              ->selectRaw('DATE_FORMAT(created_at, "%M %d, %Y") as booking_date')
                              ->whereDoctorId(auth()->id())
                              ->whereIsCancelled(false)
                              ->whereIsDeleted(false)
                              ->get();
      return $appointments;
    
  }
}
