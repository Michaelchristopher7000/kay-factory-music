<?php

namespace App\Http\Requests\Release;

use App\Models\Release;
use App\Models\Track;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReleaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by ReleasePolicy
    }

    public function rules(): array
    {
        $release = $this->route('release');
        $releaseId = $release?->id;

        // Resolve the target artist: newly supplied, or fall back to the existing release.
        $targetArtistId = $this->input('artist_id') ?? $release?->artist_id;

        return [
            'artist_id' => ['sometimes', 'integer', 'exists:artists,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', Rule::in(Release::TYPES)],
            'status' => ['sometimes', 'string', Rule::in(Release::STATUSES)],
            'release_date' => ['nullable', 'date'],
            'pre_save_date' => ['nullable', 'date'],
            'upc' => [
                'nullable', 'string', 'max:14',
                Rule::unique('releases', 'upc')->ignore($releaseId),
            ],
            'description' => ['nullable', 'string'],
            'cover_art_path' => ['nullable', 'string', 'max:500'],
            'label_copy' => ['nullable', 'string'],

            // Tracks must match the target release artist.
            'track_ids' => ['sometimes', 'array'],
            'track_ids.*' => [
                'integer',
                Rule::exists('tracks', 'id')->where(function ($query) use ($targetArtistId) {
                    $query->where('artist_id', $targetArtistId);
                }),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $release = $this->route('release');
            $newArtistId = $this->input('artist_id');
            $newTrackIds = $this->input('track_ids');

            // If artist is being changed, we require track_ids to be supplied as well,
            // otherwise the existing pivot rows could reference a different artist.
            if (
                $newArtistId !== null
                && $release
                && (int) $newArtistId !== (int) $release->artist_id
                && $newTrackIds === null
            ) {
                $v->errors()->add(
                    'track_ids',
                    'Track IDs must be provided when changing the release artist.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'track_ids.*.exists' => 'All tracks must belong to the release artist.',
        ];
    }
}