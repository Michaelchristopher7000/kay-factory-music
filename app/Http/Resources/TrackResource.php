<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TrackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'track_code' => $this->track_code,
            'artist_id' => $this->artist_id,
            'title' => $this->title,
            'isrc' => $this->isrc,
            'duration_seconds' => $this->duration_seconds,
            'genre' => $this->genre,
            'language' => $this->language,
            'bpm' => $this->bpm,
            'key' => $this->key,
            'is_explicit' => (bool) $this->is_explicit,
            'composer' => $this->composer,
            'writers' => $this->writers ?? [],
            'producers' => $this->producers ?? [],
            'featured_artists' => $this->featured_artists ?? [],
            'recorded_date' => $this->recorded_date?->toDateString(),
            'lyrics' => $this->lyrics,
            'audio_path' => $this->audio_path,
            'notes' => $this->notes,

            'artist' => $this->whenLoaded('artist', function () {
                return $this->artist ? [
                    'id' => $this->artist->id,
                    'artist_code' => $this->artist->artist_code,
                    'name' => $this->artist->name,
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