<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                'required_without:mobile',
            ],

            'mobile' => [
                'nullable',
                'string',
                'max:30',
                'required_without:email',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',

            'email.required_without' => 'Either email or mobile is required.',
            'mobile.required_without' => 'Either email or mobile is required.',

            'email.email' => 'Please provide a valid email address.',
        ];
    }
}
