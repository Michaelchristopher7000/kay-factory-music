<?php

namespace App\Http\Requests\Release;

use App\Models\Release;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by ReleasePolicy
    }

    public function rules(): array
    {
        $artistId = $this->input('artist_id');

        return [
            'artist_id' => ['required', 'integer', 'exists:artists,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(Release::TYPES)],
            'status' => ['sometimes', 'string', Rule::in(Release::STATUSES)],
            'release_date' => ['nullable', 'date'],
            'pre_save_date' => ['nullable', 'date'],
            'upc' => ['nullable', 'string', 'max:14', 'unique:releases,upc'],
            'description' => ['nullable', 'string'],
            'cover_art_path' => ['nullable', 'string', 'max:500'],
            'label_copy' => ['nullable', 'string'],

            // Tracks must belong to the same artist as the release.
            'track_ids' => ['sometimes', 'array'],
            'track_ids.*' => [
                'integer',
                Rule::exists('tracks', 'id')->where(function ($query) use ($artistId) {
                    $query->where('artist_id', $artistId);
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'track_ids.*.exists' => 'All tracks must belong to the release artist.',
        ];
    }
}