<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponser;
use App\Http\Controllers\Controller;
use App\Http\Resources\Doctor\DoctorResource;
use App\Http\Resources\User\UserResource;
use App\Models\Content;
use App\Models\DoctorAvailability;
use App\Models\DoctorProfile;
use App\Models\EmergencyContact;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    use ApiResponser;

    /** User login */
    public function login(Request $request)
    {
        $this->validate($request, [
            'email'       =>  'required|email|exists:users,email',
            'device_type' =>  'in:ios,android,web'
        ]);

        $user = User::where('email', $request->email)->first(); 
        
        if($user->is_verified == 1){
            if($user->is_blocked == 0){
                $user->verified_code =  123456; // mt_rand(100000,900000);
                $user->save();

                $data = [
                    'user_id' => $user->id
                ];

                return $this->successDataResponse('Please enter verification.', $data, 200);
            }
            else{
                return $this->errorResponse('Your account is blocked.', 400);
            }
        }
        else{
            return $this->errorResponse('Your account is not verfied.', 400);
        }
    }

    /** User register */
    public function register(Request $request)
    {
        $this->validate($request, [
            'email'         =>  'required|unique:users|email|max:255',
            'user_type'     =>  'required|in:user,doctor,pharmacy,labortory'
        ]);
        
        $created =  User::create($request->only('email','user_type'));

        if($created){
            $data = [
                'user_id' => $created->id
            ];
            return $this->successDataResponse('Please enter verification.', $data, 200);
        }
        else{
            return $this->errorResponse('Something went wrong.', 400);
        }
    }


    /** User verification */
    public function verification(Request $request)
    {
        $this->validate($request, [
            'user_id'       => 'required|exists:users,id',
            'verified_code' => 'required|min:6|max:6',
            'type'          => 'required|in:forgot,account_verify'
        ]);

        $userExists = User::whereId($request->user_id)->where('verified_code', $request->verified_code)->exists();

        if($userExists){
            if($request->type == 'forgot'){
                $updateUser = User::whereId($request->user_id)->where('verified_code', $request->verified_code)->update(['is_forgot' => '1', 'verified_code' => null]);
            }else{
                $updateUser = User::whereId($request->user_id)->where('verified_code', $request->verified_code)->update(['is_verified' => '1', 'verified_code' => 123456]);
            }

            if($updateUser){
                $user = User::find($request->user_id);
                $token = $user->createToken('AuthToken');
                $user_type = $user->user_type;

                if($user_type == 'user'){
                    $userResource = new UserResource($user);
                } elseif($user_type == 'doctor'){
                    $userResource = new DoctorResource($user);
                }
                else{
                    $userResource = [];
                }
                
                return $this->loginResponse('Your verification complete successfully.', $token->plainTextToken, $userResource);
            }
            else{
                return $this->errorResponse('Something went wrong.', 400);
            }
        }
        else{
            return $this->errorResponse('Invalid details.', 400);
        }
    }

    /** Resend code */
    public function reSendCode(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users,id'
        ]);

        $code = 123456; // mt_rand(100000,900000);
        $update_user = User::whereId($request->user_id)->update(['verified_code' => $code]);

        if($update_user){
            return $this->successResponse('Resend code successfully send on your given email.', 200);
        }
        else{
            return $this->errorResponse('Something went wrong.', 400);
        }
    }

    /** User complete profile  */
    public function userCompleteProfile($request)
    {
        $userObj = $request->only(['first_name', 'last_name', 'gender', 'profile_image']);       
        $userProfileObj = $request->only(['height_feet', 'height_inches', 'weight', 'weight_type', 'address', 'past_consultant_advice', 'past_allergies', 'past_diseases', 'past_symptoms', 'current_consultant_advice', 'current_allergies', 'current_diseases', 'current_symptoms']);

        if($request->hasFile('profile_image')){
            $profile_image = $request->profile_image->store('public/profile_image');
            $path = Storage::url($profile_image);
            $userObj['profile_image'] = $path;
        }
        $userObj['is_profile_complete'] = '1';
        
        $update_user = User::whereId(auth()->user()->id)->update($userObj);

        $userProfileObj['past_allergies']    = json_encode($request->past_allergies);
        $userProfileObj['past_diseases']     = json_encode($request->past_diseases);
        $userProfileObj['past_symptoms']     = json_encode($request->past_symptoms);
        $userProfileObj['current_allergies'] = json_encode($request->current_allergies);
        $userProfileObj['current_diseases']  = json_encode($request->current_diseases);
        $userProfileObj['current_symptoms']  = json_encode($request->current_symptoms);

        if(UserProfile::where('user_id', auth()->user()->id)->exists()){
            $update_user_profile = UserProfile::where('user_id', auth()->user()->id)->update($userProfileObj);
        }
        else{
            $userProfileObj['user_id'] = auth()->user()->id;
            $created_user_profile = UserProfile::insert($userProfileObj);
        }

        // User Emergency Contact
        if(isset($request->emergency_contact)){
            foreach($request->emergency_contact as $emergency_contact){
                $emergencyContact = new EmergencyContact;
                $emergencyContact->user_id = auth()->user()->id;
                $emergencyContact->first_name = $emergency_contact['first_name'];
                $emergencyContact->last_name = $emergency_contact['last_name'];
                $emergencyContact->contact_number = $emergency_contact['contact_number'];
                $emergencyContact->relation = $emergency_contact['relation'];
                $emergencyContact->save();
            }
        }
        return true;
    }

    /** User complete profile  */
    public function doctorCompleteProfile($request)
    {
        $doctorObj = $request->only(['first_name', 'last_name', 'gender', 'profile_image']);       
        $doctorProfileObj = $request->only(['language', 'date_of_birth', 'phone_number', 'address', 'city', 'zip_code', 'state', 'specialty', 'year_of_experience', 'hospital_clinic', 'appointment_type', 'consultation_fee']);

        if($request->hasFile('profile_image')){
            $profile_image = $request->profile_image->store('public/profile_image');
            $path = Storage::url($profile_image);
            $doctorObj['profile_image'] = $path;
        }
        $doctorObj['is_profile_complete'] = '1';
        
        $update_doctor = User::whereId(auth()->user()->id)->update($doctorObj);

        if(DoctorProfile::where('user_id', auth()->user()->id)->exists()){
            $update_doctor_profile = DoctorProfile::where('user_id', auth()->user()->id)->update($doctorProfileObj);
        }
        else{
            $doctorProfileObj['user_id'] = auth()->user()->id;
            $created_doctor_profile = DoctorProfile::insert($doctorProfileObj);
        }

        // Doctor Availability
        if(isset($request->doctor_availability)){
            foreach($request->doctor_availability as $key => $availability){
                $doctorAvailability = new DoctorAvailability;
                $doctorAvailability->user_id = auth()->user()->id;
                $doctorAvailability->day = $availability['day'];
                $doctorAvailability->time = $availability['time'];
                $doctorAvailability->save();
            }
        }
        return true;
    }    

    /** Complete profile */
    public function completeProfile(Request $request)
    {
        $this->validate($request, [
            'profile_image'       =>    'mimes:jpeg,png,jpg',
            'date_of_birth'       =>    'date_format:Y-m-d'
        ]);

        $user_type = auth()->user()->user_type;
        $data = $request->all();

        if($request->hasFile('profile_image')){
            $profile_image = $request->profile_image->store('public/profile_image');
            $path = Storage::url($profile_image);
            $completeProfile['profile_image'] = $path;
        }
        $completeProfile['is_profile_complete'] = '1';

        if($user_type == 'user'){
            $this->userCompleteProfile($request);
            $user = User::whereId(auth()->user()->id)->with('user_profile')->first();
            $userResource = new UserResource($user);
            return $this->successDataResponse('Profile complete successfully.', $userResource);
        }
        elseif($user_type == 'doctor'){
            $this->doctorCompleteProfile($request);
            $user = User::whereId(auth()->user()->id)->with('doctor_profile', 'doctor_availability')->first();
            $doctorResource = new DoctorResource($user);
            return $this->successDataResponse('Profile complete successfully.', $doctorResource);
        }
    }

    /** Content */
    public function content(Request $request)
    {
        $this->validate($request, [
            'type' => 'required|exists:contents,type'
        ]);

        $content = Content::where('type', $request->type)->first();
        return $this->successDataResponse('Content found.', $content, 200);
    }

    /** Content */
    public function logout(Request $request)
    {
        $deleteTokens = $request->user()->currentAccessToken()->delete();
        
        if($deleteTokens){
            $update_user = User::whereId(auth()->user()->id)->update(['device_type' => null, 'device_token' => null]);
            if($update_user){
                return $this->successResponse('User logout successfully.', 200);
            }else{
                return $this->errorResponse('Something went wrong.', 400);
            }
        }else{
            return $this->errorResponse('Something went wrong11.', 400);
        }                
    }
}
