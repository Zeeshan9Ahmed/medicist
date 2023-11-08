<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\Auth\LoginRequest;
use App\Http\Requests\Api\User\Auth\SocialLoginRequest;
use App\Http\Resources\LoggedInUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        
        if ( Auth::attempt(['email' => $request->email, 'password' => $request->password]) )
        {
            $user = Auth::user();

            if ( $user->email_verified_at == null)
            {
                return commonErrorMessage("Account not verified Please Verify your account", 400);
            }
            
            $user->device_type = $request->device_type;
            $user->device_token = $request->device_token;
            $user->save();
            // $user->tokens()->delete();
            // $token =$user->createToken('authToken')->plainTextToken;
            $url = 'test';
            return apiSuccessMessage("User login Successfully", new LoggedInUser($user, $url), '$token');
        }

        return commonErrorMessage("Invalid Credientials", 400);
    }

    public function logout()
    {
        $user = Auth::user();
        $user->tokens()->delete();
        $user->device_type = null;
        $user->device_token = null;
        $user->save();
        
        return commonSuccessMessage('Log Out Successfully');

    }
    public function socialAuth(SocialLoginRequest $request) 
    {
        if ($request->signin_mode == 'phone') {
            return $this->phoneSignIn($request);
        }
        $user = User::where(['social_token' => $request->social_token , 'social_type' => $request->social_type])->first();
        
        if(!$user) {
            $user = new User();
            $user->social_token = $request->social_token;
            $user->social_type = $request->social_type;
            $user->is_social = 1;
            $user->role = $request->role;
        }

        if ($user->role !== $request->role) {
            return commonErrorMessage("Account already exists", 400);
        }
        if(!$user->email_verified_at){
            $user->email_verified_at = Carbon::now();
        }
        
        $user->signin_mode = 'social';

        $user->device_type = $request->device_type;
        $user->device_token = $request->device_token;
        $user->save();
        

        $user->tokens()->delete();
        $token = $user->createToken('authToken')->plainTextToken;
        
        return apiSuccessMessage("login Successfully", new LoggedInUser(getProfile($user->id, $user->role)), $token);
        
    }

    public function phoneSignIn($user_data) {
        // return $user->phone;
        $user = User::where(['phone_number' => $user_data->phone_number ])->first();
        if(!$user){
            $user = new User();
            $user->social_token = $user_data->social_token;
            $user->social_type = $user_data->social_type;
            $user->phone_number = $user_data->phone_number;
            $user->is_social = 1;
            $user->role = $user_data->role;

            $user->save();
            // $user = User::whereId($user->id)->first();
        }


        if ($user->role !== $user_data->role) {
            return commonErrorMessage("Account already exists", 400);
        }
        
        
        $user->signin_mode = 'phone';
        $user->device_type = $user_data->device_type;
        $user->device_token = $user_data->device_token;
        $user->save();
        // $user = User::whereId($user->id)->first();
        $user->tokens()->delete();
        $token = $user->createToken('authToken')->plainTextToken;

        return apiSuccessMessage("login Successfully", new LoggedInUser(getProfile($user->id, $user->role)), $token);


    }
}
