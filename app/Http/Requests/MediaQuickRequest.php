<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaQuickRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            "alt"     => ["nullable", "string"],
            "caption" => ["nullable", "string"],
            "media"   => ["nullable"],
        ];
    }

    public function messages()
    {
        return [
            'alt.string'     => 'The alt text must be a string.',
            'caption.string' => 'The caption must be a string.',
        ];
    }

    public function withValidator($validator): void
    {
        $isUpdate = $this->route('slug') ? true : false;

        $validator->after(function ($validator) use ($isUpdate) {
            $data = $validator->getData();

            if (! $isUpdate && ! $this->hasFile('media')) {
                $validator->errors()->add('media', 'The media field is required when creating a new record.');
            }
        });
    }
}
