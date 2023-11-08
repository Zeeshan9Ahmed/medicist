<?php


namespace App\Services\OTP;


use App\Mail\AccountVerificationOTPMail;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class GenerateOTP
{

    /**
     * Create user.
     *
     * @param  array  $data
     * @return OTPCode
     */
    public function execute(array $data) : OTPCode
    {
        
        return OtpCode::create([
            'user_id' => $data['id'] ?? null,
            'email' => $data['email'],
            'ref' => $data['type'],
           'code' => rand(100001,999999),
            'code' => 123456,
            // 'reference_code' => rand(100001,999999),
            'expiring_at' => Carbon::now()->addMinutes(60),
        ]);
    }
}
