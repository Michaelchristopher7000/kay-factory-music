<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartConversationRequest;
use App\Http\Requests\StoreStaffMessageRequest;
use App\Http\Resources\StaffConversationResource;
use App\Http\Resources\StaffMessageResource;
use App\Models\StaffConversation;
use App\Models\StaffMessage;
use App\Models\User;
use App\Notifications\StaffMessageReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class StaffMessageController extends Controller
{
    /**
     * List all conversations for the authenticated staff user.
     */
    public function conversations(Request $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $conversations = StaffConversation::query()
            ->forUser($userId)
            ->with([
                'userOne:id,name,email,avatar,role_id',
                'userOne.role:id,name',
                'userTwo:id,name,email,avatar,role_id',
                'userTwo.role:id,name',
                'messages' => function ($q) {
                    $q->latest()->limit(1);
                },
            ])
            ->withCount([
                'messages as unread_count' => function ($q) use ($userId) {
                    $q->whereNull('read_at')->where('sender_id', '!=', $userId);
                },
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        return StaffConversationResource::collection($conversations);
    }

    /**
     * Show a specific conversation with its messages (marks unread as read).
     */
    public function show(Request $request, StaffConversation $conversation): JsonResponse
    {
        $userId = $request->user()->id;

        // Authorization: user must be a participant
        if (! in_array($userId, [$conversation->user_one_id, $conversation->user_two_id], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $conversation->load([
            'userOne:id,name,email,avatar,role_id',
            'userOne.role:id,name',
            'userTwo:id,name,email,avatar,role_id',
            'userTwo.role:id,name',
        ]);

        // Mark all messages from the other user as read
        StaffMessage::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Load messages oldest → newest, capped at 200 most recent
        $messages = StaffMessage::where('conversation_id', $conversation->id)
            ->with('sender:id,name,avatar')
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->sortBy('id')
            ->values();

        return response()->json([
            'data' => [
                'conversation' => (new StaffConversationResource($conversation))->resolve(),
                'messages' => StaffMessageResource::collection($messages)->resolve(),
            ],
        ]);
    }

    /**
     * Send a message in an existing conversation.
     */
    public function send(Request $request, StaffConversation $conversation, StoreStaffMessageRequest $form): JsonResponse
    {
        $userId = $request->user()->id;

        if (! in_array($userId, [$conversation->user_one_id, $conversation->user_two_id], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $message = DB::transaction(function () use ($conversation, $form, $userId) {
            $msg = StaffMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'body' => $form->validated()['body'],
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $msg;
        });

        $message->load('sender:id,name,avatar');

        // Notify the other participant
        $this->notifyOtherParticipant($conversation, $message, $userId);

        return (new StaffMessageResource($message))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Start a new conversation (or reuse an existing one) with a first message.
     */
    public function start(StartConversationRequest $request): JsonResponse
    {
        $senderId = $request->user()->id;
        $recipientId = (int) $request->validated()['user_id'];

        if ($senderId === $recipientId) {
            return response()->json(['message' => 'You cannot message yourself.'], 422);
        }

        $recipient = User::find($recipientId);
        if (! $recipient || ! $recipient->role) {
            return response()->json(['message' => 'Recipient not found.'], 404);
        }

        $conversation = StaffConversation::findOrCreateBetween($senderId, $recipientId);

        $message = DB::transaction(function () use ($conversation, $request, $senderId) {
            $msg = StaffMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'body' => $request->validated()['body'],
            ]);

            $conversation->update(['last_message_at' => now()]);

            return $msg;
        });

        $message->load('sender:id,name,avatar');

        $this->notifyOtherParticipant($conversation, $message, $senderId);

        return response()->json([
            'data' => [
                'conversation_id' => $conversation->id,
                'message' => (new StaffMessageResource($message))->resolve(),
            ],
        ], 201);
    }

    /**
     * List all users that can be messaged (everyone except self).
     */
    public function recipients(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $users = User::query()
            ->with('role:id,name')
            ->where('id', '!=', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'avatar', 'role_id']);

        return response()->json([
            'data' => $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'avatar_url' => $u->avatar_url,
                'role' => $u->role?->name,
            ]),
        ]);
    }

    /**
     * Total unread messages for the authenticated staff user.
     * Used by the topbar badge.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $count = StaffMessage::query()
            ->whereHas('conversation', function ($q) use ($userId) {
                $q->where('user_one_id', $userId)
                  ->orWhere('user_two_id', $userId);
            })
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => ['unread_count' => $count],
        ]);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    protected function notifyOtherParticipant(
        StaffConversation $conversation,
        StaffMessage $message,
        int $senderId,
    ): void {
        try {
            $otherId = $conversation->otherUserId($senderId);
            $recipient = User::find($otherId);
            if (! $recipient) return;

            Notification::send(
                $recipient,
                new StaffMessageReceivedNotification(
                    $message,
                    $conversation->id,
                    $message->sender->name,
                ),
            );
        } catch (Throwable $e) {
            report($e);
        }
    }
}