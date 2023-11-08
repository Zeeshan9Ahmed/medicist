<?php

namespace App\Http\Requests\Api\User\CoreModule;

use Illuminate\Foundation\Http\FormRequest;

class AllowPermissionRequest extends FormRequest
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
            'type' => 'required|in:receivePushNotification,location,lightBirdMainLed,flashyBirdFlasher,navBirdNavLight,loudBirdHorn'
        ];
    }
}
