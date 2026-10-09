<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicArtistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $avatar = $this->avatar;

        // Convert stored Supabase paths into public image URLs.
        if (
            $avatar &&
            !filter_var($avatar, FILTER_VALIDATE_URL)
        ) {
            $baseUrl = rtrim(
                config('filesystems.disks.supabase.public_url', ''),
                '/'
            );

            $bucket = config(
                'filesystems.disks.supabase.bucket',
                'kfm-media'
            );

            if ($baseUrl !== '') {
                $encodedPath = implode(
                    '/',
                    array_map(
                        'rawurlencode',
                        explode('/', ltrim($avatar, '/'))
                    )
                );

                $avatar = $baseUrl
                    . '/storage/v1/object/public/'
                    . $bucket
                    . '/'
                    . $encodedPath;
            }
        }

        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'genre' => $this->genre,
            'country' => $this->country,
            'city' => $this->city,
            'bio' => $this->bio,
            'avatar' => $avatar,
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
