<?php

namespace App\Http\Requests\Api\User\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SignUpRequest extends FormRequest
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
            // 'signin_mode' => 'required|in:email,phone',
            'email' => 'required|email',
            'role' => 'required|in:user,doctor,pharmacy,labortory',
            // 'phone' => 'required_if:signin_mode,phone|regex:/(01)[0-9]{9}/',
        ];
    }

    
}
