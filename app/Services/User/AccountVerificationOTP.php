<?php


namespace App\Services\User;


use App\Exceptions\AudioBookException;
use App\Mail\AccountVerificationOTPMail;
// use App\Models\OTPCode;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\BaseService;
use App\Services\OTP\GenerateOTP;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class AccountVerificationOTP
{


    /**
     * Create user.
     *
     * @param  array  $data
     * @return OTPCode
     */
    public function execute(array $data): OTPCode
    {
        $user = $data['user'];
        
        $find = OtpCode::where(['email'=>$user->email,'ref'=>'ACCOUNT_VERIFICATION'])->delete();

        $otp = app(GenerateOTP::class)->execute(['type'=>'ACCOUNT_VERIFICATION','email'=>$user->email]);
        // print_r($user->email);
        // Mail::to($user->email)->send(new AccountVerificationOTPMail($user,$otp));
        return $otp;
    }
}
