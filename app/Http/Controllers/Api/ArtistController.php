<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Artist\StoreArtistRequest;
use App\Http\Requests\Artist\UpdateArtistRequest;
use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ArtistCreatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ArtistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Artist::class);

        $perPage = max(
            1,
            min((int) $request->input('per_page', 15), 100)
        );

        $artists = Artist::query()
            ->with(['manager:id,name,email', 'createdBy:id,name,email'])
            ->search($request->input('search'))
            ->status($request->input('status'))
            ->genre($request->input('genre'))
            ->managedBy($request->input('manager_id'))
            ->orderByDesc('id')
            ->paginate($perPage);

        return ArtistResource::collection($artists);
    }

    public function store(StoreArtistRequest $request): JsonResponse
    {
        Gate::authorize('create', Artist::class);

        $uploadedAvatarPath = null;

        try {
            $artist = DB::transaction(function () use (
                $request,
                &$uploadedAvatarPath
            ) {
                $data = $request->validated();

                $data['created_by'] = $request->user()->id;

                // Upload the selected image to Supabase Storage.
                if ($request->hasFile('avatar')) {
                    $uploadedAvatarPath = $this->uploadAvatar(
                        $request->file('avatar')
                    );

                    $data['avatar'] = $uploadedAvatarPath;
                }

                // Generate the artist code when it is not provided.
                if (empty($data['artist_code'])) {
                    $lastArtist = Artist::lockForUpdate()
                        ->orderBy('id', 'desc')
                        ->first();

                    $nextId = $lastArtist
                        ? $lastArtist->id + 1
                        : 1;

                    $data['artist_code'] = 'KFM-ART-' .
                        str_pad($nextId, 4, '0', STR_PAD_LEFT);
                }

                return Artist::create($data);
            });
        } catch (Throwable $e) {
            // Remove the uploaded file if artist creation fails.
            if ($uploadedAvatarPath) {
                try {
                    Storage::disk('supabase')->delete(
                        $uploadedAvatarPath
                    );
                } catch (Throwable $storageError) {
                    Log::error(
                        'Failed to clean up artist image after creation failed.',
                        [
                            'path' => $uploadedAvatarPath,
                            'error' => $storageError->getMessage(),
                        ]
                    );
                }
            }

            throw $e;
        }

        // Notify Super Admins. Notification failures must not undo creation.
        try {
            $superAdminRole = Role::where(
                'slug',
                'super-admin'
            )->first();

            if ($superAdminRole) {
                $superAdmins = User::where(
                    'role_id',
                    $superAdminRole->id
                )->get();

                if ($superAdmins->isNotEmpty()) {
                    Notification::send(
                        $superAdmins,
                        new ArtistCreatedNotification(
                            $artist->id,
                            $artist->artist_code,
                            $artist->name
                        )
                    );
                }
            }
        } catch (Throwable $e) {
            Log::error(
                'Artist created, but Super Admin notification failed.',
                [
                    'artist_id' => $artist->id,
                    'error' => $e->getMessage(),
                ]
            );
        }

        // Notify the assigned manager separately.
        if (!empty($artist->manager_id)) {
            try {
                $manager = User::find($artist->manager_id);

                if ($manager) {
                    $manager->notify(
                        new ArtistCreatedNotification(
                            $artist->id,
                            $artist->artist_code,
                            $artist->name
                        )
                    );
                }
            } catch (Throwable $e) {
                Log::error(
                    'Artist created, but manager notification failed.',
                    [
                        'artist_id' => $artist->id,
                        'manager_id' => $artist->manager_id,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        $artist->load([
            'manager:id,name,email',
            'createdBy:id,name,email',
        ]);

        return (new ArtistResource($artist))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Artist $artist): ArtistResource
    {
        Gate::authorize('view', $artist);

        $artist->load([
            'manager:id,name,email',
            'createdBy:id,name,email',
        ]);

        return new ArtistResource($artist);
    }

    public function update(
        UpdateArtistRequest $request,
        Artist $artist
    ): ArtistResource {
        Gate::authorize('update', $artist);

        $data = $request->validated();

        // Preserve the old image unless a replacement is uploaded.
        $oldAvatarPath = $artist->avatar;
        $newAvatarPath = null;

        if ($request->hasFile('avatar')) {
            $newAvatarPath = $this->uploadAvatar(
                $request->file('avatar')
            );

            $data['avatar'] = $newAvatarPath;
        } else {
            // Never overwrite the existing avatar with a missing value.
            unset($data['avatar']);
        }

        try {
            $artist->update($data);
        } catch (Throwable $e) {
            // If saving fails, remove the newly uploaded image.
            if ($newAvatarPath) {
                try {
                    Storage::disk('supabase')->delete(
                        $newAvatarPath
                    );
                } catch (Throwable $storageError) {
                    Log::error(
                        'Failed to clean up replacement artist image.',
                        [
                            'path' => $newAvatarPath,
                            'error' => $storageError->getMessage(),
                        ]
                    );
                }
            }

            throw $e;
        }

        // Delete only a previous image managed by this feature.
        if (
            $newAvatarPath &&
            $oldAvatarPath &&
            !filter_var($oldAvatarPath, FILTER_VALIDATE_URL) &&
            str_starts_with($oldAvatarPath, 'artists/')
        ) {
            try {
                Storage::disk('supabase')->delete($oldAvatarPath);
            } catch (Throwable $e) {
                Log::warning(
                    'Artist updated, but the old image could not be deleted.',
                    [
                        'artist_id' => $artist->id,
                        'path' => $oldAvatarPath,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        $artist->load([
            'manager:id,name,email',
            'createdBy:id,name,email',
        ]);

        return new ArtistResource($artist);
    }

    public function destroy(Artist $artist): JsonResponse
    {
        Gate::authorize('delete', $artist);

        $avatarPath = $artist->avatar;

        $artist->delete();

        // Clean up an uploaded image after the artist is deleted.
        if (
            $avatarPath &&
            !filter_var($avatarPath, FILTER_VALIDATE_URL) &&
            str_starts_with($avatarPath, 'artists/')
        ) {
            try {
                Storage::disk('supabase')->delete($avatarPath);
            } catch (Throwable $e) {
                Log::warning(
                    'Artist deleted, but the image could not be removed.',
                    [
                        'artist_id' => $artist->id,
                        'path' => $avatarPath,
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        return response()->json([
            'message' => 'Artist deleted successfully.',
        ]);
    }

    /**
     * Upload an artist image to the public Supabase bucket.
     */
    private function uploadAvatar($file): string
    {
        $path = 'artists/' . Str::uuid() . '.' . $file->extension();

        Storage::disk('supabase')->put(
            $path,
            file_get_contents($file->getRealPath()),
            [
                'ContentType' => $file->getMimeType(),
            ]
        );

        return $path;
    }
}
