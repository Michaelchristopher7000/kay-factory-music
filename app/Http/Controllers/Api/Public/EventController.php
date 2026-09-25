<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistEventResource;
use App\Models\ArtistEvent;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function next(): JsonResponse
    {
        $event = ArtistEvent::query()
            ->published()
            ->where('event_date', '>=', now())
            ->with('artist:id,slug,name,avatar,genre')
            ->orderBy('event_date')
            ->first();

        if (! $event) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => array_merge(
                (new ArtistEventResource($event))->resolve(),
                [
                    'artist' => $event->artist ? [
                        'id' => $event->artist->id,
                        'slug' => $event->artist->slug,
                        'name' => $event->artist->name,
                        'avatar' => $event->artist->avatar,
                        'genre' => $event->artist->genre,
                    ] : null,
                ],
            ),
        ]);
    }
}