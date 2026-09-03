<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GamePlayResultRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'player_id' => ['required', 'integer', 'exists:players,id'],
            'duration_ms' => ['required', 'integer', 'min:0'],
            'backtracks' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'player_id.required' => 'Player is required.',
            'player_id.integer' => 'Player ID must be a valid integer.',
            'player_id.exists' => 'The selected player does not exist.',

            'duration_ms.required' => 'Game duration is required.',
            'duration_ms.integer' => 'Game duration must be a valid integer.',
            'duration_ms.min' => 'Game duration cannot be negative.',

            'backtracks.required' => 'Backtracks count is required.',
            'backtracks.integer' => 'Backtracks must be a valid integer.',
            'backtracks.min' => 'Backtracks cannot be negative.',
        ];
    }
}
