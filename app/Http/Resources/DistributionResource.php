<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'distribution_code' => $this->distribution_code,
            'release_id' => $this->release_id,
            'platform' => $this->platform,
            'distributor' => $this->distributor,
            'status' => $this->status,
            'territory' => $this->territory,
            'scheduled_for' => $this->scheduled_for?->toDateString(),
            'submitted_at' => $this->submitted_at?->toDateString(),
            'live_at' => $this->live_at?->toDateString(),
            'takedown_at' => $this->takedown_at?->toDateString(),
            'platform_release_id' => $this->platform_release_id,
            'platform_url' => $this->platform_url,
            'notes' => $this->notes,

            'release' => $this->whenLoaded('release', function () {
                return $this->release ? [
                    'id' => $this->release->id,
                    'release_code' => $this->release->release_code,
                    'title' => $this->release->title,
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