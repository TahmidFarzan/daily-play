<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthUserAccountRequest extends FormRequest
{
    public function contributorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "name"                  => ["required", "string", "max:200"],
            'email'                 => ["email", "max:255", Rule::unique('users')->ignore(Auth::id(), 'id')],

            "change_password"       => ["required", "boolean"],
            "password"              => ["nullable", "string", "min:8", "confirmed"],
            "current_password"      => ["nullable", "string"],
            "password_confirmation" => ["nullable", "string"],
        ];
    }

    public function messages()
    {
        return [
            'name.required'                     => 'The name field is required.',
            'name.string'                       => 'The name must be a string.',
            'name.max'                          => 'The name may not be greater than 200 characters.',

            'email.required'                    => 'The email field is required.',
            'email.string'                      => 'The email must be a string.',
            'email.max'                         => 'The email may not be greater than 255 characters.',
            'email.unique'                      => 'The email has already been taken.',

            'password.required_if'              => 'The password field is required when changing password.',
            'password.string'                   => 'The password must be a string.',
            'password.min'                      => 'The password must be at least 8 characters.',
            'password.confirmed'                => 'The password confirmation does not match.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $aVData = $validator->getData();

            if (isset($aVData["change_password"]) && $aVData["change_password"] == true) {
                if (! isset($aVData["password"]) || empty($aVData['password'])) {
                    $validator->errors()->add(
                        'password', 'The password field is required when changing password.'
                    );
                }

                if (! isset($aVData["current_password"]) || empty($aVData['current_password'])) {
                    $validator->errors()->add(
                        'current_password', 'The current password field is required when changing password.'
                    );
                }

                if (! isset($aVData["password_confirmation"]) || empty($aVData['password_confirmation'])) {
                    $validator->errors()->add(
                        'password_confirmation', 'The password confirmation field is required when changing password.'
                    );
                }

            }

        });
    }
}
