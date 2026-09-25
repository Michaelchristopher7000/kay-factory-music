<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ArtistEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'artist_id' => $this->artist_id,
            'title' => $this->title,
            'description' => $this->description,
            'venue' => $this->venue,
            'city' => $this->city,
            'country' => $this->country,
            'event_date' => $this->event_date,
            'event_time' => $this->event_time,
            'ticket_url' => $this->ticket_url,
            'event_url' => $this->event_url,
            'image_url' => $this->image_path
                ? Storage::disk('public')->url($this->image_path)
                : null,
            'status' => $this->status,
            'is_upcoming' => $this->event_date && $this->event_date->isFuture(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}