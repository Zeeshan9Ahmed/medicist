<?php

namespace App\Http\Controllers\Api\User\OTP;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\OTP\OtpVerifyRequest;
use App\Http\Resources\LoggedInUser;
use App\Services\OTP\AccountVerification;
use App\Services\OTP\ValidateOTP;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function otpVerify(OtpVerifyRequest $request) 
    {
        $data = $request->only('email', 'reference_code', 'type');
        $check_otp = app(ValidateOTP::class)->execute($data);
        
        if($request->type === "ACCOUNT_VERIFICATION")
        {
            $user = app(AccountVerification::class)->execute(['email'=>$check_otp->email]);
            
            $user->tokens()->delete();
            $token =$user->createToken('authToken')->plainTextToken;
            
            return apiSuccessMessage("Account Verified Successfully", new LoggedInUser($user), $token);
        }elseif($request->type==="PASSWORD_RESET"){
            
            return commonSuccessMessage("Success", 200);
        }
    }
}
