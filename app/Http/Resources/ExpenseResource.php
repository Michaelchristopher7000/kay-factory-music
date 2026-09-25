<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'expense_code' => $this->expense_code,
            'category' => $this->category,
            'artist_id' => $this->artist_id,
            'release_id' => $this->release_id,
            'track_id' => $this->track_id,
            'distribution_id' => $this->distribution_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'incurred_at' => $this->incurred_at?->toDateString(),
            'paid_at' => $this->paid_at?->toDateString(),
            'reference' => $this->reference,
            'description' => $this->description,
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