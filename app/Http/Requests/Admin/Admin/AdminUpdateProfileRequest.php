<?php

namespace App\Http\Requests\Admin\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateProfileRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        if($this->method() == "GET"){
            return [];
        }else if($this->method() == "POST"){
            if($this->update_section == 'update_profile_information'){
                return [
                    // 'email'=>'required|unique:users,email,'.auth()->user()->id,
                    'first_name' => 'required|min:3|max:255',
                    'last_name' => 'required|min:3|max:255',
                    'profile_image' => 'sometimes|required|image|mimes:jpeg,jpg,png,gif,svg|max:4096',
                ];
            }else{
                if($this->update_section == 'update_password_information'){
                    return [
                        'current_password' => 'required',
                        'password' => 'required|min:6|confirmed',
                    ];
                }
            }
        }    
        
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response =  webcommonErrorMessage($validator->errors()->first());
        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
