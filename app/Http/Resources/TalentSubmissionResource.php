<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TalentSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,

            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'location' => $this->location,

            'talent_category' => $this->talent_category,
            'bio' => $this->bio,
            'message' => $this->message,
            'social_links' => $this->social_links ?? [],

            'has_audio' => (bool) $this->audio_path,
            'has_video' => (bool) $this->video_path,
            'has_image' => (bool) $this->image_path,

            // URLs to authenticated download endpoints (never direct paths)
            'audio_url' => $this->audio_path
                ? url("/api/talent-submissions/{$this->id}/media/audio")
                : null,
            'video_url' => $this->video_path
                ? url("/api/talent-submissions/{$this->id}/media/video")
                : null,
            'image_url' => $this->image_path
                ? url("/api/talent-submissions/{$this->id}/media/image")
                : null,

            'status' => $this->status,
            'manager_notes' => $this->manager_notes,

            'reviewed_by' => $this->whenLoaded('reviewer', function () {
                return $this->reviewer ? [
                    'id' => $this->reviewer->id,
                    'name' => $this->reviewer->name,
                    'email' => $this->reviewer->email,
                ] : null;
            }),
            'reviewed_at' => $this->reviewed_at,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}