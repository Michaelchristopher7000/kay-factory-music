<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'body' => $this->body,
            'is_read' => $this->read_at !== null,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'sender' => $this->whenLoaded('sender', function () {
                return $this->sender ? [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                    'avatar_url' => $this->sender->avatar_url,
                ] : null;
            }),
        ];
    }
}