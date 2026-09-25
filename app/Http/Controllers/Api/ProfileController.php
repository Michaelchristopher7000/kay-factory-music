<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
     * Upload or replace the authenticated user's avatar.
     */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();
        $oldAvatar = $user->avatar;

        // Store the new file FIRST — Laravel generates a safe random name.
        $path = $request->file('avatar')->store('avatars', 'public');

        // Update DB
        $user->avatar = $path;
        $user->save();

        // Now safely remove the old file (only if it was a local file)
        if ($oldAvatar && ! preg_match('#^https?://#i', $oldAvatar)) {
            Storage::disk('public')->delete($oldAvatar);
        }

        AuditLog::log('avatar_updated', $user, [
            'attributes' => ['avatar' => $path],
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

        if (! $oldAvatar) {
            return response()->json([
                'message' => 'No profile picture to remove.',
                'user' => new UserResource($user->fresh(['role'])),
            ]);
        }

        $user->avatar = null;
        $user->save();

        if (! preg_match('#^https?://#i', $oldAvatar)) {
            Storage::disk('public')->delete($oldAvatar);
        }

        AuditLog::log('avatar_removed', $user, [
            'attributes' => ['avatar' => null],
        ]);

        return response()->json([
            'message' => 'Profile picture removed.',
            'user' => new UserResource($user->fresh(['role'])),
        ]);
    }
}