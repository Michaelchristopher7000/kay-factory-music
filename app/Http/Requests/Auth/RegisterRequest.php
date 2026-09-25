<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'role_id'  => ['required', 'exists:roles,id'],
        ];
    }

    public function messages(): array
    {
        $min = (int) config('kfm.password.min_length', 12);

        return [
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min'       => "The password must be at least {$min} characters.",
        ];
    }
}