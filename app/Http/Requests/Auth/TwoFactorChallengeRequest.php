<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint, gated by challenge_token
    }

    public function rules(): array
    {
        return [
            'challenge_token' => ['required', 'string', 'size:64'],
            'code'            => ['required', 'string', 'min:6', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'challenge_token.required' => 'The verification session is missing or has expired.',
            'challenge_token.size'     => 'The verification session is invalid.',
            'code.required'            => 'The verification code is invalid or has expired.',
            'code.min'                 => 'The verification code is invalid or has expired.',
            'code.max'                 => 'The verification code is invalid or has expired.',
        ];
    }
}