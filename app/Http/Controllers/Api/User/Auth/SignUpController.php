<?php

namespace App\Http\Controllers\Api\User\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\Auth\ResendSignUpOtpRequest;
use App\Http\Requests\Api\User\Auth\SignUpRequest;
use App\Models\User;
use App\Services\User\AccountVerificationOTP;
use App\Services\User\CreateUser;
use Illuminate\Http\Request;
use App\Http\Resources\LoggedInUser;
use App\Services\Payment\PaymentService;

class SignUpController extends Controller
{


    public function signIn(SignUpRequest $request, PaymentService $service)
    {
        
        $data = $request->only(['email', 'device_type', 'device_token', 'role']);
        // $data['password'] = bcrypt($request->password);
        $check = User::where('email', $request->email)->first();
        
        if (!$check) {
            $user = app(CreateUser::class)->execute($data);

            $sendOtp = app(AccountVerificationOTP::class)->execute(['user' => $user]);
            $role = $request->role;
            if ($role == 'user') {
                $user->customer_id = $service->createCustomer()?->id;
            }

            if ($role == 'doctor') {
                $user->account_number = $service->createExpressAccount()?->id;
            }
            
            $user->save();
            return apiSuccessMessage("Signin Successfully", ['id' => $user->id, 'email' => $user->email]);
        }

        if ($check->role !== $data['role']) {
            return commonErrorMessage("Account already exists", 400);
        }

        $sendOtp = app(AccountVerificationOTP::class)->execute(['user' => $check]);

        $check->device_type = $request->device_type;
        $check->device_token = $request->device_token;
        $check->save();
        return apiSuccessMessage("Signin Successfully", new LoggedInUser($check));
    }


    public function resendSignUpOtp(ResendSignUpOtpRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return commonErrorMessage("No User Found", 404);
        }

        // if ( $user->email_verified_at != null )
        // {
        //     return commonErrorMessage("Account already verified", 400);
        // }

        $otp_code = app(AccountVerificationOTP::class)->execute(['user' => $user]);

        return commonSuccessMessage("Verification Code Sent Successfully", 200);
    }
}
