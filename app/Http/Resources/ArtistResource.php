<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArtistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $avatar = $this->avatar;

        // Convert Supabase object paths into public image URLs.
        // Keep existing absolute URLs unchanged.
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
            'id' => $this->id,
            'artist_code' => $this->artist_code,
            'name' => $this->name,
            'real_name' => $this->real_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'avatar' => $avatar,
            'genre' => $this->genre,
            'country' => $this->country,
            'city' => $this->city,
            'status' => $this->status,
            'social_links' => $this->social_links,

            'manager' => $this->whenLoaded('manager', function () {
                return $this->manager ? [
                    'id' => $this->manager->id,
                    'name' => $this->manager->name,
                    'email' => $this->manager->email,
                ] : null;
            }),

            'created_by' => $this->whenLoaded('createdBy', function () {
                return $this->createdBy ? [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ] : null;
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}