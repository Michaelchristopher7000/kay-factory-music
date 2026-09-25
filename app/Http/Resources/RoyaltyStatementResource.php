<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoyaltyStatementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statement_code' => $this->statement_code,
            'artist_id' => $this->artist_id,
            'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(),
            'status' => $this->status,
            'currency' => $this->currency,
            'royalty_rate' => $this->royalty_rate,
            'total_revenue' => $this->total_revenue,
            'total_royalty' => $this->total_royalty,
            'total_paid' => $this->total_paid,
            'balance' => $this->balance,
            'issued_at' => $this->issued_at?->toDateString(),
            'notes' => $this->notes,

            'artist' => $this->whenLoaded('artist', function () {
                return $this->artist ? [
                    'id' => $this->artist->id,
                    'artist_code' => $this->artist->artist_code,
                    'name' => $this->artist->name,
                ] : null;
            }),

            'lines' => $this->whenLoaded('lines', function () {
                return RoyaltyStatementLineResource::collection($this->lines)->resolve();
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