<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_code' => $this->contract_code,
            'artist_id' => $this->artist_id,
            'title' => $this->title,
            'type' => $this->type,
            'status' => $this->status,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'signed_date' => $this->signed_date?->toDateString(),
            'advance_amount' => $this->advance_amount,
            'currency' => $this->currency,
            'royalty_rate' => $this->royalty_rate,
            'terms' => $this->terms,
            'document_path' => $this->document_path,

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