<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevenueEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entry_code' => $this->entry_code,
            'source' => $this->source,
            'platform' => $this->platform,
            'artist_id' => $this->artist_id,
            'release_id' => $this->release_id,
            'track_id' => $this->track_id,
            'distribution_id' => $this->distribution_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'received_at' => $this->received_at?->toDateString(),
            'reference' => $this->reference,
            'notes' => $this->notes,

            'artist' => $this->whenLoaded('artist', function () {
                return $this->artist ? [
                    'id' => $this->artist->id,
                    'artist_code' => $this->artist->artist_code,
                    'name' => $this->artist->name,
                ] : null;
            }),

            'release' => $this->whenLoaded('release', function () {
                return $this->release ? [
                    'id' => $this->release->id,
                    'release_code' => $this->release->release_code,
                    'title' => $this->release->title,
                ] : null;
            }),

            'track' => $this->whenLoaded('track', function () {
                return $this->track ? [
                    'id' => $this->track->id,
                    'track_code' => $this->track->track_code,
                    'title' => $this->track->title,
                ] : null;
            }),

            'distribution' => $this->whenLoaded('distribution', function () {
                return $this->distribution ? [
                    'id' => $this->distribution->id,
                    'distribution_code' => $this->distribution->distribution_code,
                    'platform' => $this->distribution->platform,
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