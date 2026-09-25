<?php

namespace App\Http\Requests\Track;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by TrackPolicy
    }

    public function rules(): array
    {
        $trackId = $this->route('track')?->id;

        return [
            'artist_id' => ['sometimes', 'integer', 'exists:artists,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'isrc' => [
                'nullable', 'string', 'max:12',
                Rule::unique('tracks', 'isrc')->ignore($trackId),
            ],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'genre' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'max:10'],
            'bpm' => ['nullable', 'integer', 'min:1', 'max:400'],
            'key' => ['nullable', 'string', 'max:10'],
            'is_explicit' => ['sometimes', 'boolean'],
            'composer' => ['nullable', 'string', 'max:255'],
            'writers' => ['nullable', 'array'],
            'writers.*' => ['string', 'max:255'],
            'producers' => ['nullable', 'array'],
            'producers.*' => ['string', 'max:255'],
            'featured_artists' => ['nullable', 'array'],
            'featured_artists.*' => ['string', 'max:255'],
            'recorded_date' => ['nullable', 'date'],
            'lyrics' => ['nullable', 'string'],
            'audio_path' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ];
    }
}