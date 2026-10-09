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

class ArtistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Artist::class);

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

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

        // Create and save the artist before attempting notifications.
        $artist = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = $request->user()->id;

            // Generate artist_code if not provided.
            if (empty($data['artist_code'])) {
                $lastArtist = Artist::lockForUpdate()
                    ->orderBy('id', 'desc')
                    ->first();

                $nextId = $lastArtist ? $lastArtist->id + 1 : 1;

                $data['artist_code'] = 'KFM-ART-' .
                    str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }

            return Artist::create($data);
        });

        // Notify Super Admins. Notification errors must not undo artist creation.
        try {
            $superAdminRole = Role::where('slug', 'super-admin')->first();

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
        } catch (\Throwable $e) {
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
                    $manager->notify(new ArtistCreatedNotification(
                        $artist->id,
                        $artist->artist_code,
                        $artist->name
                    ));
                }
            } catch (\Throwable $e) {
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

        $artist->load(['manager:id,name,email', 'createdBy:id,name,email']);

        return new ArtistResource($artist);
    }

    public function update(
        UpdateArtistRequest $request,
        Artist $artist
    ): ArtistResource {
        Gate::authorize('update', $artist);

        $artist->update($request->validated());

        $artist->load(['manager:id,name,email', 'createdBy:id,name,email']);

        return new ArtistResource($artist);
    }

    public function destroy(Artist $artist): JsonResponse
    {
        Gate::authorize('delete', $artist);

        $artist->delete();

        return response()->json([
            'message' => 'Artist deleted successfully.',
        ]);
    }
}
