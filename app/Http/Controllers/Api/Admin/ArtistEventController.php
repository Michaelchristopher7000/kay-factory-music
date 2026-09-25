<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Artist\StoreArtistEventRequest;
use App\Http\Requests\Artist\UpdateArtistEventRequest;
use App\Http\Resources\ArtistEventResource;
use App\Models\ArtistEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ArtistEventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $events = ArtistEvent::query()
            ->when($request->input('artist_id'), fn ($q, $id) => $q->where('artist_id', $id))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('event_date')
            ->paginate($perPage);

        return ArtistEventResource::collection($events);
    }

    public function store(StoreArtistEventRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')
                ->store("artists/{$data['artist_id']}/events", 'public');
        }
        unset($data['image']);

        $event = ArtistEvent::create($data);

        return (new ArtistEventResource($event))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ArtistEvent $event): ArtistEventResource
    {
        return new ArtistEventResource($event);
    }

    public function update(UpdateArtistEventRequest $request, ArtistEvent $event): ArtistEventResource
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($event->image_path) {
                Storage::disk('public')->delete($event->image_path);
            }
            $data['image_path'] = $request->file('image')
                ->store("artists/{$event->artist_id}/events", 'public');
        }
        unset($data['image']);

        $event->update($data);

        // Reload from the DB to pick up casts and relationships.
        // Fall back to the in-memory model if fresh() returns null.
        $fresh = $event->fresh() ?? $event;

        return new ArtistEventResource($fresh);
    }

    public function destroy(ArtistEvent $event): JsonResponse
    {
        if ($event->image_path) {
            Storage::disk('public')->delete($event->image_path);
        }

        $event->delete();

        return response()->json(['message' => 'Event deleted.']);
    }
}