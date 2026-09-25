<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoyaltyStatementLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statement_id' => $this->statement_id,
            'revenue_entry_id' => $this->revenue_entry_id,
            'contract_id' => $this->contract_id,
            'release_id' => $this->release_id,
            'track_id' => $this->track_id,
            'source' => $this->source,
            'description' => $this->description,
            'revenue_amount' => $this->revenue_amount,
            'royalty_rate' => $this->royalty_rate,
            'royalty_amount' => $this->royalty_amount,

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

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}