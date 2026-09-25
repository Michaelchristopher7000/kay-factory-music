<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->title,
            'type' => $this->type,
            'release_date' => $this->release_date?->toDateString(),
            'cover_art_path' => $this->cover_art_path,
            'label_copy' => $this->label_copy,
            'description' => $this->description,

            'artist' => $this->whenLoaded('artist', function () {
                return $this->artist ? [
                    'slug' => $this->artist->slug,
                    'name' => $this->artist->name,
                    'genre' => $this->artist->genre,
                    'avatar' => $this->artist->avatar,
                ] : null;
            }),

            'tracks' => $this->whenLoaded('tracks', function () {
                return $this->tracks->map(fn ($track) => [
                    'title' => $track->title,
                    'duration_seconds' => $track->duration_seconds,
                    'is_explicit' => (bool) $track->is_explicit,
                ])->values();
            }),
        ];
    }
}