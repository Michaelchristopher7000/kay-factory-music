<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoyaltyPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_code' => $this->payment_code,
            'statement_id' => $this->statement_id,
            'artist_id' => $this->artist_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'paid_at' => $this->paid_at?->toDateString(),
            'method' => $this->method,
            'reference' => $this->reference,
            'notes' => $this->notes,

            'statement' => $this->whenLoaded('statement', function () {
                return $this->statement ? [
                    'id' => $this->statement->id,
                    'statement_code' => $this->statement->statement_code,
                    'status' => $this->statement->status,
                ] : null;
            }),

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