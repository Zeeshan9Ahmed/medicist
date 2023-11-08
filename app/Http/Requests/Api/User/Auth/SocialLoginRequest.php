<?php

namespace App\Http\Requests\Api\User\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SocialLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'signin_mode' => 'required|in:social,phone',
            'social_type' => 'required_if:signin_mode,social',
            'social_token' => 'required_if:signin_mode,social',
            'phone_number' => 'required_if:signin_mode,phone',
            'device_type' => 'required|in:ios,android',
            'device_token' => 'required',
            'role' => 'required|in:user,doctor,pharmacy,labortory',
        ];
    }
}
