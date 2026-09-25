<?php

namespace App\Http\Requests;

use App\Models\ContactMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'category' => [
                'required',
                'string',
                Rule::in(array_keys(ContactMessage::CATEGORIES)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'email.required' => 'Please enter your email address.',
            'subject.required' => 'Please enter a subject.',
            'message.required' => 'Please write your message.',
            'message.min' => 'Your message is too short — please add more detail.',
            'category.required' => 'Please select a reason for contact.',
            'category.in' => 'Please select a valid category.',
        ];
    }
}