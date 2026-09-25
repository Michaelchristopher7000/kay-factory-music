<?php

namespace App\Http\Requests\Artist;

use App\Models\ArtistVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArtistVideoRequest extends FormRequest
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

            'type' => [
                'sometimes',
                Rule::in([
                    ArtistVideo::TYPE_MUSIC_VIDEO,
                    ArtistVideo::TYPE_VISUALIZER,
                    ArtistVideo::TYPE_LIVE,
                    ArtistVideo::TYPE_INTERVIEW,
                    ArtistVideo::TYPE_BEHIND_THE_SCENES,
                ]),
            ],

            'source' => [
                'sometimes',
                Rule::in([
                    ArtistVideo::SOURCE_YOUTUBE,
                    ArtistVideo::SOURCE_UPLOAD,
                ]),
            ],

            'youtube_url' => ['nullable', 'url', 'max:500'],

            'video' => [
                'nullable',
                'file',
                'mimetypes:video/mp4,video/webm,video/quicktime',
                'max:512000',
            ],

            'thumbnail' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'published_at' => ['nullable', 'date'],
            'is_featured' => ['nullable', 'boolean'],
            'is_home_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => [
                'sometimes',
                Rule::in([
                    ArtistVideo::STATUS_DRAFT,
                    ArtistVideo::STATUS_PUBLISHED,
                ]),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $source = $this->input('source');

            if ($source === ArtistVideo::SOURCE_YOUTUBE) {
                $url = $this->input('youtube_url');
                if ($url && ! ArtistVideo::extractYoutubeId($url)) {
                    $v->errors()->add(
                        'youtube_url',
                        'We could not extract a video ID from this YouTube URL.'
                    );
                }
            }
        });
    }
}