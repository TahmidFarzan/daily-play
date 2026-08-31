<?php
namespace App\Http\Requests;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ["required", "max:255"],

            'email'                 => ["required", "email", "max:255"],
            'mobile'                => ["nullable", "max:20", "regex:/^[+0-9 ]+$/"],

            'birth_date'            => ["nullable", "date"],
            'gender'                => ["required", "in:Male,Female,Other"],
            'religion'              => ["required", "in:Islam,Hindu,Christian,Other"],
            'marital_status'        => ["required", "in:Single,Married,Divorce,Separated,Other"],

            'address'               => ["nullable"],

            "set_as_verify_email"   => ["required", "boolean"],
            "change_password"       => ["required", "boolean"],

            'user_permission_ids'   => ["nullable"],

            "password"              => ["nullable", "string", "min:8", "confirmed"],
            "password_confirmation" => ["nullable", "string"],

            'profile_image'         => ["nullable", "image", "mimes:jpg,jpeg,png,webp"],
        ];
    }

    public function messages()
    {
        return [
            'name.required'                => 'The name field is required.',
            'name.string'                  => 'The name must be a string.',
            'name.max'                     => 'The name may not be greater than 255 characters.',

            'email.required'               => 'The email field is required.',
            'email.string'                 => 'The email must be a string.',
            'email.max'                    => 'The email may not be greater than 255 characters.',
            'email.unique'                 => 'This email is already registered.',

            'birth_date.date'              => 'The birth date must be a valid date.',

            'gender.required'              => 'The gender field is required.',
            'religion.required'            => 'The religion field is required.',
            'marital_status.required'      => 'The marital status field is required.',

            'mobile.max'                   => 'The mobile number may not be greater than 20 characters.',
            'mobile.regex'                 => 'The mobile number format is invalid. Only numbers, spaces, and + are allowed.',

            'change_password.required'     => 'The change password field is required.',
            'change_password.boolean'      => 'The change password field must be true or false.',

            'set_as_verify_email.required' => 'The verify email field is required.',
            'set_as_verify_email.boolean'  => 'The verify email field must be true or false.',

            'password.string'              => 'The password must be a string.',
            'password.min'                 => 'The password must be at least 8 characters.',
            'password.confirmed'           => 'The password confirmation does not match.',

            'profile_image.image'          => 'The profile image must be an image file.',
            'profile_image.mimes'          => 'The profile image must be a file of type: jpg, jpeg, png, webp.',
            'profile_image.dimensions'     => 'The profile image has invalid dimensions.',

        ];
    }

    public function withValidator($validator)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $user = User::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($user, $authUser) {
            $aVData = $validator->getData();

            if (! $aVData['is_super_admin']){

                if (! array_key_exists(
                    'user_permission_ids',
                    $aVData
                )) {
                    $validator->errors()->add(
                        'user_permission_ids',
                        'The user permissions field is required.'
                    );
                }
            }

            if (array_key_exists('user_permission_ids', $aVData)){
                $permissionIds = array_filter( (array) ( $aVData[ 'user_permission_ids'] ?? []) );

                if (count($permissionIds)) {

                    $count = UserPermission::whereIn('id', $permissionIds)->count();

                    if ($count !== count($permissionIds)) {
                        $validator->errors()->add('user_permission_ids', 'One or more selected permissions do not exist.');
                    }
                }
            }

            if (isset($aVData["email"])) {
                $sameCount = User::where("email", $aVData["email"]);
                if ($user) {
                    $sameCount = $sameCount->where("id", "!=", $user->id);
                }

                $sameCount = $sameCount->count();

                if ($sameCount > 0) {
                    $validator->errors()->add(
                        'email',
                        'This email is already registered.',
                    );
                }
            }

            if (isset($aVData["change_password"]) && $aVData["change_password"] == true) {
                if (! isset($aVData["password"]) || empty($aVData['password'])) {
                    $validator->errors()->add(
                        'password',
                        'The password field is required when changing password.',
                    );
                }

                if (! isset($aVData["password_confirmation"]) || empty($aVData['password_confirmation'])) {
                    $validator->errors()->add(
                        'password_confirmation',
                        'The password confirmation field is required when changing password.',
                    );
                }
            }
        });
    }
}
