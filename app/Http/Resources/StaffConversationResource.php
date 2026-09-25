<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUserId = $request->user()?->id;
        $otherId = $currentUserId ? $this->otherUserId($currentUserId) : null;
        $other = $this->whenLoaded('userOne', function () use ($otherId) {
            if (! $otherId) return null;
            return $otherId === $this->user_one_id
                ? $this->userOne
                : $this->userTwo;
        });

        // Note: we preload both userOne and userTwo in the controller for this to work.

        return [
            'id' => $this->id,
            'user_one_id' => $this->user_one_id,
            'user_two_id' => $this->user_two_id,
            'other_user' => $other ? [
                'id' => $other->id,
                'name' => $other->name,
                'email' => $other->email,
                'avatar_url' => $other->avatar_url,
                'role' => $other->role?->name,
            ] : null,
            'last_message_at' => $this->last_message_at,
            'last_message' => $this->whenLoaded('messages', function () {
                $latest = $this->messages->first();
                return $latest ? [
                    'body' => \Illuminate\Support\Str::limit($latest->body, 120),
                    'created_at' => $latest->created_at,
                    'is_read' => $latest->read_at !== null,
                    'sender_id' => $latest->sender_id,
                ] : null;
            }),
            'unread_count' => $this->unread_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}