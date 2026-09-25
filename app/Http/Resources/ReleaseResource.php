<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'release_code' => $this->release_code,
            'artist_id' => $this->artist_id,
            'title' => $this->title,
            'type' => $this->type,
            'status' => $this->status,
            'release_date' => $this->release_date?->toDateString(),
            'pre_save_date' => $this->pre_save_date?->toDateString(),
            'upc' => $this->upc,
            'description' => $this->description,
            'cover_art_path' => $this->cover_art_path,
            'label_copy' => $this->label_copy,

            'artist' => $this->whenLoaded('artist', function () {
                return $this->artist ? [
                    'id' => $this->artist->id,
                    'artist_code' => $this->artist->artist_code,
                    'name' => $this->artist->name,
                ] : null;
            }),

            'tracks' => $this->whenLoaded('tracks', function () {
                return $this->tracks->map(fn ($track) => [
                    'id' => $track->id,
                    'track_code' => $track->track_code,
                    'title' => $track->title,
                    'position' => (int) $track->pivot->position,
                ])->values();
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