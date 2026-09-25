<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Release\StoreReleaseRequest;
use App\Http\Requests\Release\UpdateReleaseRequest;
use App\Http\Resources\ReleaseResource;
use App\Models\Release;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ReleaseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Release::class);

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $releases = Release::query()
            ->with(['artist:id,artist_code,name', 'createdBy:id,name,email'])
            ->search($request->input('search'))
            ->forArtist($request->input('artist_id'))
            ->type($request->input('type'))
            ->status($request->input('status'))
            ->forYear($request->input('year'))
            ->orderByDesc('id')
            ->paginate($perPage);

        return ReleaseResource::collection($releases);
    }

    public function store(StoreReleaseRequest $request): JsonResponse
    {
        Gate::authorize('create', Release::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $trackIds = $data['track_ids'] ?? [];
        unset($data['track_ids']);

        $release = Release::create($data);
        $this->syncTracks($release, $trackIds);

        $release->load(['artist:id,artist_code,name', 'createdBy:id,name,email', 'tracks']);

        return (new ReleaseResource($release))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Release $release): ReleaseResource
    {
        Gate::authorize('view', $release);

        $release->load(['artist:id,artist_code,name', 'createdBy:id,name,email', 'tracks']);

        return new ReleaseResource($release);
    }

    public function update(UpdateReleaseRequest $request, Release $release): ReleaseResource
    {
        Gate::authorize('update', $release);

        $data = $request->validated();
        $hasTrackIds = array_key_exists('track_ids', $data);
        $trackIds = $data['track_ids'] ?? [];
        unset($data['track_ids']);

        $release->update($data);

        if ($hasTrackIds) {
            $this->syncTracks($release, $trackIds);
        }

        $release->load(['artist:id,artist_code,name', 'createdBy:id,name,email', 'tracks']);

        return new ReleaseResource($release);
    }

    public function destroy(Release $release): JsonResponse
    {
        Gate::authorize('delete', $release);

        $release->delete();

        return response()->json([
            'message' => 'Release deleted successfully.',
        ]);
    }

    /**
     * Sync tracks onto a release, setting position by array order.
     *
     * @param  array<int>  $trackIds
     */
    private function syncTracks(Release $release, array $trackIds): void
    {
        if (empty($trackIds)) {
            $release->tracks()->sync([]);
            return;
        }

        $syncData = [];
        foreach (array_values($trackIds) as $index => $trackId) {
            $syncData[$trackId] = ['position' => $index + 1];
        }

        $release->tracks()->sync($syncData);
    }
}