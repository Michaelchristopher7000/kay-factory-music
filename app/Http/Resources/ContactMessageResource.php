<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,

            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,

            'subject' => $this->subject,
            'message' => $this->message,
            'category' => $this->category,
            'category_label' => \App\Models\ContactMessage::CATEGORIES[$this->category] ?? $this->category,

            'status' => $this->status,
            'internal_notes' => $this->internal_notes,

            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return $this->assignedTo ? [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                ] : null;
            }),

            'read_at' => $this->read_at,
            'replied_at' => $this->replied_at,
            'resolved_at' => $this->resolved_at,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}