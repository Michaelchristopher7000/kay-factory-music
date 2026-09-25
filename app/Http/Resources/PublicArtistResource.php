<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicArtistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'genre' => $this->genre,
            'country' => $this->country,
            'city' => $this->city,
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'social_links' => $this->social_links ?? [],

            'releases_count' => $this->whenCounted('releases'),
            'tracks_count' => $this->whenCounted('tracks'),

            'releases' => PublicReleaseResource::collection(
                $this->whenLoaded('releases')
            ),

            'videos' => ArtistVideoResource::collection(
                $this->whenLoaded('videos')
            ),

            'gallery' => ArtistGalleryImageResource::collection(
                $this->whenLoaded('gallery')
            ),

            'events' => ArtistEventResource::collection(
                $this->whenLoaded('events')
            ),
        ];
    }
}