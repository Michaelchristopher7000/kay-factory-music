<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password'         => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        $min = (int) config('kfm.password.min_length', 12);

        return [
            'current_password.required'         => 'Enter your current password.',
            'current_password.current_password' => 'The current password is incorrect.',
            'password.required'                 => 'Enter a new password.',
            'password.confirmed'                => 'The password confirmation does not match.',
            'password.min'                      => "The new password must be at least {$min} characters.",
        ];
    }
}