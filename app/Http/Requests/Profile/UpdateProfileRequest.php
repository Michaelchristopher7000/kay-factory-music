<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // operates on the authenticated user only
    }

    public function rules(): array
    {
        $user = $this->user();

        $emailIsChanging = $this->filled('email')
            && strtolower($this->input('email')) !== strtolower($user->email);

        return [
            'name' => ['sometimes', 'string', 'max:255'],

            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            // Only required when the email is actually changing
            'current_password' => [
                Rule::requiredIf($emailIsChanging),
                'nullable',
                'string',
                'current_password',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Enter your current password to change your email address.',
            'current_password.current_password' => 'The current password is incorrect.',
        ];
    }
}