<?php

namespace App\Http\Requests\Artist;

use App\Models\ArtistGalleryImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArtistGalleryImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'artist_id' => ['sometimes', 'integer', 'exists:artists,id'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],
            'caption' => ['nullable', 'string', 'max:500'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_featured' => ['nullable', 'boolean'],
            'status' => [
                'sometimes',
                Rule::in([
                    ArtistGalleryImage::STATUS_DRAFT,
                    ArtistGalleryImage::STATUS_PUBLISHED,
                ]),
            ],
        ];
    }
}