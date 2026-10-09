<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Update the authenticated user's name and/or email.
     * Email change requires the current password.
     */
    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        $before = [
            'name' => $user->name,
            'email' => $user->email,
        ];

        $data = $request->validated();

        unset($data['current_password']);

        $user->fill($data);

        $dirty = array_keys($user->getDirty());

        if (empty($dirty)) {
            return new UserResource($user->fresh(['role']));
        }

        $user->save();

        $after = [];
        $beforeFiltered = [];

        foreach ($dirty as $field) {
            $after[$field] = $user->$field;
            $beforeFiltered[$field] = $before[$field] ?? null;
        }

        AuditLog::log('profile_updated', $user, [
            'before' => $beforeFiltered,
            'after' => $after,
        ]);

        return new UserResource($user->fresh(['role']));
    }

    /**
     * Upload or replace the authenticated user's avatar in Supabase.
     */
    public function uploadAvatar(
        UploadAvatarRequest $request
    ): JsonResponse {
        $user = $request->user();

        $oldAvatar = $user->avatar;
        $file = $request->file('avatar');

        $path = 'avatars/' . Str::uuid()
            . '.' . $file->extension();

        // Upload the new image before changing the database.
        Storage::disk('supabase')->put(
            $path,
            file_get_contents($file->getRealPath()),
            [
                'ContentType' => $file->getMimeType(),
            ]
        );

        try {
            $user->avatar = $path;
            $user->save();
        } catch (Throwable $e) {
            // Clean up the new image if saving the user fails.
            try {
                Storage::disk('supabase')->delete($path);
            } catch (Throwable $storageError) {
                Log::error(
                    'Failed to clean up staff avatar after profile save failed.',
                    [
                        'user_id' => $user->id,
                        'path' => $path,
                        'error' => $storageError->getMessage(),
                    ]
                );
            }

            throw $e;
        }

        // Remove the previous image only after the new one is saved.
        if ($oldAvatar && $oldAvatar !== $path) {
            try {
                if (str_starts_with($oldAvatar, 'avatars/')) {
                    // Older staff avatars saved in Supabase.
                    Storage::disk('supabase')->delete($oldAvatar);
                } elseif (
                    !filter_var($oldAvatar, FILTER_VALIDATE_URL)
                ) {
                    // Older staff avatars saved locally.
                    Storage::disk('public')->delete($oldAvatar);
                }
            } catch (Throwable $e) {
                Log::warning(
                    'Staff avatar updated, but the previous image could not be deleted.',
                    [
                        'user_id' => $user->id,
                        'old_avatar' => $oldAvatar,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        AuditLog::log('avatar_updated', $user, [
            'attributes' => [
                'avatar' => $path,
            ],
        ]);

        return response()->json([
            'message' => 'Profile picture updated.',
            'user' => new UserResource($user->fresh(['role'])),
        ]);
    }

    /**
     * Remove the authenticated user's avatar.
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();
        $oldAvatar = $user->avatar;

        if (!$oldAvatar) {
            return response()->json([
                'message' => 'No profile picture to remove.',
                'user' => new UserResource($user->fresh(['role'])),
            ]);
        }

        // Clear the database first.
        $user->avatar = null;
        $user->save();

        // Remove the image from the storage system that owns it.
        try {
            if (str_starts_with($oldAvatar, 'avatars/')) {
                Storage::disk('supabase')->delete($oldAvatar);
            } elseif (
                !filter_var($oldAvatar, FILTER_VALIDATE_URL)
            ) {
                Storage::disk('public')->delete($oldAvatar);
            }
        } catch (Throwable $e) {
            Log::warning(
                'Staff avatar removed from profile, but the image file could not be deleted.',
                [
                    'user_id' => $user->id,
                    'old_avatar' => $oldAvatar,
                    'error' => $e->getMessage(),
                ]
            );
        }

        AuditLog::log('avatar_removed', $user, [
            'attributes' => [
                'avatar' => null,
            ],
        ]);

        return response()->json([
            'message' => 'Profile picture removed.',
            'user' => new UserResource($user->fresh(['role'])),
        ]);
    }
}