<?php

namespace App\Http\Requests\Artist;

use App\Models\Artist;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArtistRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by ArtistPolicy.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'real_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'bio' => ['nullable', 'string'],

            // Artist image: maximum 5 MB.
            'avatar' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp,gif',
                'max:5120',
            ],

            'genre' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],

            'status' => [
                'sometimes',
                'string',
                Rule::in(Artist::STATUSES),
            ],

            'manager_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'social_links' => ['nullable', 'array'],
            'social_links.*' => [
                'nullable',
                'url',
                'max:255',
            ],
        ];
    }
}
