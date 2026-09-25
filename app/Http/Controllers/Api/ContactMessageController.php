<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateContactMessageRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactMessageController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $messages = ContactMessage::query()
            ->with('assignedTo:id,name,email')
            ->when($request->input('search'), function ($q, $term) {
                $op = ContactMessage::likeOperator();
                $q->where(function ($q) use ($term, $op) {
                    $q->where('name', $op, "%{$term}%")
                      ->orWhere('email', $op, "%{$term}%")
                      ->orWhere('subject', $op, "%{$term}%")
                      ->orWhere('reference_number', $op, "%{$term}%");
                });
            })
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('category'), fn ($q, $c) => $q->where('category', $c))
            ->orderByDesc('id')
            ->paginate($perPage);

        return ContactMessageResource::collection($messages);
    }

    public function show(ContactMessage $contactMessage): ContactMessageResource
    {
        $contactMessage->load('assignedTo:id,name,email');

        // Auto-mark as read on first view
        if ($contactMessage->status === ContactMessage::STATUS_UNREAD) {
            $contactMessage->update([
                'status' => ContactMessage::STATUS_READ,
                'read_at' => now(),
            ]);

            AuditLog::log('contact_message.status_changed', $contactMessage, [
                'from' => ContactMessage::STATUS_UNREAD,
                'to' => ContactMessage::STATUS_READ,
                'trigger' => 'auto_on_view',
            ]);
        }

        return new ContactMessageResource($contactMessage);
    }

    public function update(
        UpdateContactMessageRequest $request,
        ContactMessage $contactMessage,
    ): ContactMessageResource {
        $data = $request->validated();
        $auditEntries = [];

        // Status change
        if (array_key_exists('status', $data) && $data['status'] !== $contactMessage->status) {
            $auditEntries[] = [
                'action' => 'contact_message.status_changed',
                'changes' => [
                    'from' => $contactMessage->status,
                    'to' => $data['status'],
                ],
            ];

            // Auto-set timestamps based on target status
            if ($data['status'] === ContactMessage::STATUS_READ && ! $contactMessage->read_at) {
                $data['read_at'] = now();
            }
            if ($data['status'] === ContactMessage::STATUS_REPLIED && ! $contactMessage->replied_at) {
                $data['replied_at'] = now();
                if (! $contactMessage->read_at) {
                    $data['read_at'] = now();
                }
            }
            if ($data['status'] === ContactMessage::STATUS_RESOLVED && ! $contactMessage->resolved_at) {
                $data['resolved_at'] = now();
                if (! $contactMessage->read_at) {
                    $data['read_at'] = now();
                }
            }
        }

        // Notes
        if (array_key_exists('internal_notes', $data)
            && $data['internal_notes'] !== $contactMessage->internal_notes) {
            $auditEntries[] = [
                'action' => 'contact_message.notes_updated',
                'changes' => [
                    'from' => $contactMessage->internal_notes,
                    'to' => $data['internal_notes'],
                ],
            ];
        }

        // Assignment
        if (array_key_exists('assigned_to', $data)
            && $data['assigned_to'] !== $contactMessage->assigned_to) {
            $auditEntries[] = [
                'action' => 'contact_message.assigned',
                'changes' => [
                    'from' => $contactMessage->assigned_to,
                    'to' => $data['assigned_to'],
                ],
            ];
        }

        $contactMessage->update($data);

        foreach ($auditEntries as $entry) {
            AuditLog::log(
                $entry['action'],
                $contactMessage,
                $entry['changes'],
            );
        }

        $contactMessage->load('assignedTo:id,name,email');

        return new ContactMessageResource($contactMessage);
    }
}