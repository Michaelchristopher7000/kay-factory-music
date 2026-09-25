<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048', // 2 MB in KB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Choose an image to upload.',
            'avatar.image' => 'The file must be a valid image.',
            'avatar.mimes' => 'Only JPEG, PNG, and WebP images are supported.',
            'avatar.max' => 'The image must be 2 MB or smaller.',
        ];
    }
}