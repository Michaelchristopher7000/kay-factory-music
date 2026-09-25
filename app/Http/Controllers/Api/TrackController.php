<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Track\StoreTrackRequest;
use App\Http\Requests\Track\UpdateTrackRequest;
use App\Http\Resources\TrackResource;
use App\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TrackController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Track::class);

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $tracks = Track::query()
            ->with(['artist:id,artist_code,name', 'createdBy:id,name,email'])
            ->search($request->input('search'))
            ->forArtist($request->input('artist_id'))
            ->genre($request->input('genre'))
            ->explicit($request->input('is_explicit'))
            ->orderByDesc('id')
            ->paginate($perPage);

        return TrackResource::collection($tracks);
    }

    public function store(StoreTrackRequest $request): JsonResponse
    {
        Gate::authorize('create', Track::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $track = Track::create($data);
        $track->load(['artist:id,artist_code,name', 'createdBy:id,name,email']);

        return (new TrackResource($track))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Track $track): TrackResource
    {
        Gate::authorize('view', $track);

        $track->load(['artist:id,artist_code,name', 'createdBy:id,name,email']);

        return new TrackResource($track);
    }

    public function update(UpdateTrackRequest $request, Track $track): TrackResource
    {
        Gate::authorize('update', $track);

        $track->update($request->validated());
        $track->load(['artist:id,artist_code,name', 'createdBy:id,name,email']);

        return new TrackResource($track);
    }

    public function destroy(Track $track): JsonResponse
    {
        Gate::authorize('delete', $track);

        $track->delete();

        return response()->json([
            'message' => 'Track deleted successfully.',
        ]);
    }
}