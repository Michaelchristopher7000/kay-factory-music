<?php

namespace App\Http\Requests\Artist;

use App\Models\ArtistEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArtistEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'artist_id' => ['sometimes', 'integer', 'exists:artists,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'venue' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'event_date' => ['sometimes', 'date'],
            'event_time' => ['nullable', 'date_format:H:i'],
            'ticket_url' => ['nullable', 'url', 'max:500'],
            'event_url' => ['nullable', 'url', 'max:500'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],
            'status' => [
                'sometimes',
                Rule::in([
                    ArtistEvent::STATUS_DRAFT,
                    ArtistEvent::STATUS_PUBLISHED,
                ]),
            ],
        ];
    }
}