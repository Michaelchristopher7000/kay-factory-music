<?php

namespace App\Http\Requests;

use App\Models\TalentSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTalentSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],

            'talent_category' => [
                'required',
                'string',
                Rule::in(TalentSubmission::CATEGORIES),
            ],

            'bio' => ['nullable', 'string', 'max:5000'],
            'message' => ['nullable', 'string', 'max:5000'],

            'social_links' => ['nullable', 'array'],
            'social_links.instagram' => ['nullable', 'url', 'max:500'],
            'social_links.tiktok' => ['nullable', 'url', 'max:500'],
            'social_links.youtube' => ['nullable', 'url', 'max:500'],
            'social_links.twitter' => ['nullable', 'url', 'max:500'],
            'social_links.facebook' => ['nullable', 'url', 'max:500'],
            'social_links.website' => ['nullable', 'url', 'max:500'],

            'audio' => [
                'nullable',
                'file',
                'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/x-wav,audio/mp4,audio/m4a,audio/x-m4a,audio/aac,audio/ogg,audio/webm',
                'max:51200', // 50 MB
            ],
            'video' => [
                'nullable',
                'file',
                'mimetypes:video/mp4,video/quicktime,video/webm',
                'max:512000', // 500 MB
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240', // 10 MB
            ],

            'consent' => ['required', 'accepted'],
        ];
    }

    /**
     * Require at least one media type (audio, video, or image).
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $hasAnyMedia =
                $this->hasFile('audio') ||
                $this->hasFile('video') ||
                $this->hasFile('image');

            if (! $hasAnyMedia) {
                $v->errors()->add(
                    'audio',
                    'Please upload at least one of: audio, video, or image.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Please enter your full name.',
            'email.required' => 'Please enter your email address.',
            'phone.required' => 'Please enter your phone number.',
            'talent_category.required' => 'Please choose a talent category.',
            'talent_category.in' => 'Please choose a valid talent category.',
            'audio.mimetypes' => 'Audio must be MP3, WAV, M4A, AAC, OGG, or WebM.',
            'audio.max' => 'Audio files must be under 50 MB.',
            'video.mimetypes' => 'Video must be MP4, MOV, or WebM.',
            'video.max' => 'Video files must be under 500 MB.',
            'image.mimes' => 'Image must be JPG, PNG, or WebP.',
            'image.max' => 'Images must be under 10 MB.',
            'consent.required' => 'You must confirm consent before submitting.',
            'consent.accepted' => 'You must confirm consent before submitting.',
        ];
    }
}