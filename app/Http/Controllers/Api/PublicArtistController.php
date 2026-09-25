<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicArtistResource;
use App\Models\Artist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicArtistController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min((int) $request->input('per_page', 12), 100));

        $artists = Artist::query()
            ->publicVisible()
            ->when($request->input('genre'), function ($q, $genre) {
                $op = Artist::likeOperator();
                $q->where('genre', $op, "%{$genre}%");
            })
            ->withCount(['releases' => fn ($q) => $q->publicVisible()])
            ->orderBy('name')
            ->paginate($perPage);

        return PublicArtistResource::collection($artists);
    }

    public function show(Artist $artist): PublicArtistResource
    {
        // Public detail — only signed artists are visible.
        abort_unless($artist->status === Artist::STATUS_SIGNED, 404);

        $artist
            ->loadCount(['releases' => fn ($q) => $q->publicVisible()])
            ->load([
                'releases' => function ($q) {
                    $q->publicVisible()
                      ->orderByDesc('release_date')
                      ->limit(12);
                },
                'videos' => function ($q) {
                    $q->published()->ordered();
                },
                'gallery' => function ($q) {
                    $q->published()->ordered();
                },
                'events' => function ($q) {
                    $q->published()->orderBy('event_date');
                },
            ]);

        return new PublicArtistResource($artist);
    }
}