<?php

namespace App\Http\Controllers\Api\User\Profile;

use App\Events\AppointmentBookedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\User\Profile\UpdateProfileRequest;
use App\Http\Resources\LoggedInUser;
use App\Models\Appointment;
use App\Models\DoctorProfile;
use App\Models\Image;
use App\Models\LabortoryPharmacyInformation;
use App\Models\Notification;
use App\Models\Photo;
use App\Models\Schedule;
use App\Models\User;
use App\Services\Notifications\CreateDBNotification;
use App\Services\Notifications\PushNotificationService;
use App\Services\User\AccountVerificationOTP;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{



    public function profile()
    {
        // return getProfile(2, "doctor");

        return apiSuccessMessage("Profile Data", new LoggedInUser(getProfile(2, "doctor")));
    }
    public function completeProfile(Request $request)
    {
        $role = auth()->user()->role;
        if ($role == "user") {
            $this->completeUserProfile($request);
            return apiSuccessMessage("Success", ['step' => 1]);
        }

        if ($role == "doctor") {
            $this->completeDoctorProfile($request);
        }

        if ($role == "pharmacy") {
            $this->completePharmacyOrLabortoryProfile($request, $role);
        }

        if ($role == "labortory") {
            $this->completePharmacyOrLabortoryProfile($request, $role);
        }

        return apiSuccessMessage("Success", new LoggedInUser(getProfile(auth()->id(), $role)));
    }

    public function completeUserProfile(Request $request)
    {

        $user =  User::find(auth()->id());
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->dob = $request->dob;
        $user->gender = $request->gender;
        $user->height_feet = $request->height_feet;
        $user->height_inch = $request->height_inch;
        $user->weight = $request->weight;
        $user->weight_type = $request->weight_type;
        $user->address = $request->address;
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip_code = $request->zip_code;

        if ($request->hasFile('avatar')) {
            $user->avatar = $this->uploadPicture($request->file('avatar'));
        }
        $user->save();
        return $user;
    }

    public function uploadPicture($picture, $upload_path = null)
    {
        $profile_image = $picture->store($upload_path ?? 'public/profile_image');
        $path = Storage::url($profile_image);
        return $path;
    }

    public function completeDoctorProfile(Request $request)
    {
        $user =  User::find(auth()->id());
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->dob = $request->dob;
        $user->gender = $request->gender;
        $user->phone_number = $request->phone_number;
        $user->language = $request->language;
        $user->address = $request->address;
        $user->latitude = $request->latitude;
        $user->longitude = $request->longitude;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip_code = $request->zip_code;

        if ($request->hasFile('avatar')) {
            $user->avatar = $this->uploadPicture($request->file('avatar'));
        }
        $user->save();
        $this->setDoctorProfile($request->only(['specialty', 'year_of_experience', 'hospital_clinic', 'appointment_type', 'consultation_fee']));
        if ($request->has('schedule')) {
            $this->setSchedule($request);
        }

        if ($request->hasFile("certificates")) {
            $this->uploadCertificates($request, auth()->id());
        }
        if ($user->profile_completed == 0) {
            $user->profile_completed = 1;
            $user->save();
        }
        return $user;
    }

    public function setDoctorProfile($profile_data)
    {
        return DoctorProfile::UpdateOrcreate(['user_id' => auth()->id()], $profile_data)->id;
    }


    public function setSchedule(Request $request)
    {
        $schedules = json_decode($request->schedule);
        // dd($schedules, json_decode($request->schedule));
        foreach ($schedules as $d) {
            $data = [
                'start_time' => $d->start_time,
                'end_time' => $d->end_time,

            ];
            if (isset($d->id)) {
                Schedule::whereId($d->id)->update($data + ['is_checked' => $d->is_checked]);
            } else {
                $data['user_id'] = auth()->id();
                $data['day'] = $d->day;
                Schedule::create($data + ['is_checked' => $d->is_checked]);
            }
        }
        return true;
    }

    public function completePharmacyOrLabortoryProfile(Request $request, $type)
    {
        DB::beginTransaction();
        try {
            //code...
            $user =  User::find(auth()->id());

            $data = $request->only(['contact_person', 'contact_number', 'years_of_experience', 'license_number']);
            if ($request->hasFile('image')) {
                $data['image'] = $this->uploadPicture($request->file('image'), "public/images");
            }
            $this->updateAdditionInformation($data);


            if ($request->hasFile("certificates")) {
                $this->uploadCertificates($request, auth()->id());
            }
            $user->first_name = $type == "pharmacy" ? $request->pharmacy_name : $request->labortory_name;
            $user->address = $request->address;
            $user->city = $request->city;
            $user->state = $request->state;
            $user->zip_code = $request->zip_code;

            $user->latitude = $request->latitude;
            $user->longitude = $request->longitude;
            // $user->license_number = $request->license_number;

            if ($request->hasFile('avatar')) {
                $user->avatar = $this->uploadPicture($request->file('avatar'));
            }

            if ($request->has('schedule')) {
                $this->setSchedule($request);
            }

            if ($user->profile_completed == 0) {
                $user->profile_completed = 1;
            }

            $user->save();
            DB::commit();
            return  $user;
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function updateAdditionInformation($data)
    {
        LabortoryPharmacyInformation::updateOrCreate(['user_id' => auth()->id()], $data);
    }


    public function uploadCertificates($files, $table_id)
    {


        foreach ($files->file('certificates') as  $file) {
            $profile_image = $file->store('public/images');
            $path = Storage::url($profile_image);

            Image::create([
                'table_id' => $table_id,
                'table_name' => 'users',
                'image_type' => 'certificates',
                'image_path' => $path,
            ]);
        }
    }

    public function toggleNotification()
    {

        if (auth()->user()->push_notification  == 1) {
            auth()->user()->push_notification = 0;
            $message = "Off";
        } else {
            auth()->user()->push_notification = 1;
            $message = "On";
        }

        auth()->user()->save();
        return commonSuccessMessage($message);
    }
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = Auth::user();
        if (!Hash::check($request->old_password, $user->password)) {
            return commonErrorMessage("InCorrect Old password , please try again", 400);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return commonErrorMessage("New Password can not be match to Old Password", 400);
        }

        $user->password = bcrypt($request->new_password);
        $user->save();
        if ($user) {
            return commonSuccessMessage("Password Updated Successfully");
        }
        return commonErrorMessage("Something went wrong while updating old password", 400);
    }

    public function content(Request $request)
    {
        // dd(url("content", $request->slug ));
        return apiSuccessMessage("Content", ['url' => url("content", $request->slug)]);
    }

    public function notifications()
    {


        $notifications = Notification::with('sender:id,first_name,last_name,email,avatar')
            ->select(
                'id',
                'from_user_id',
                'title',
                'description',
                'notification_type',
                'redirection_id',
            )
            ->where('to_user_id', auth()->id())
            ->latest()->get();
        return apiSuccessMessage("Notifications ", $notifications);
    }
}
