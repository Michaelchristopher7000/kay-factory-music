<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $message = DB::transaction(function () use ($data) {
                $data['status'] = ContactMessage::STATUS_UNREAD;

                $message = ContactMessage::create($data);

                AuditLog::log(
                    'created',
                    $message,
                    ['attributes' => $message->getAttributes()],
                );

                return $message;
            });

            $this->notifyStaff($message);

            return response()->json([
                'message' => 'Your message has been received. We will get back to you soon.',
                'reference_number' => $message->reference_number,
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'We could not process your message. Please try again.',
            ], 500);
        }
    }

    protected function notifyStaff(ContactMessage $message): void
    {
        try {
            $recipients = User::query()
                ->whereHas('role', function ($q) {
                    $q->whereIn('slug', ['super-admin', 'label-manager']);
                })
                ->get();

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send(
                $recipients,
                new ContactMessageReceivedNotification($message),
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}