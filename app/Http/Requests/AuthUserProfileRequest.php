<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AuthUserProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'              => ["required", "max:255"],
            'birth_date'        => ["nullable", "date"],
            'gender'            => ["required", "in:Male,Female,Other"],
            'religion'          => ["required", "in:Islam,Hindu,Christian,Other"],
            'marital_status'    => ["required", "in:Single,Married,Divorce,Separated,Other"],
            'mobile'            => ["nullable", "max:20", "regex:/^[+0-9 ]+$/"],

            'address'           => ["nullable"],
        ];
    }

    public function messages()
    {
        return [
            'name.required'           => 'The name field is required.',
            'name.max'                => 'The name may not be greater than 255 characters.',

            'birth_date.date'         => 'The birth date must be a valid date.',

            'gender.required'         => 'The gender field is required.',
            'religion.required'       => 'The religion field is required.',
            'marital_status.required' => 'The marital status field is required.',

            'mobile.max'              => 'The mobile number may not be greater than 20 characters.',
            'mobile.regex'            => 'The mobile number format is invalid. Only numbers, spaces, and + are allowed.',
        ];
    }
}
