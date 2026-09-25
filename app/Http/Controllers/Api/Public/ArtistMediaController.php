<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistEventResource;
use App\Http\Resources\ArtistGalleryImageResource;
use App\Http\Resources\ArtistVideoResource;
use App\Models\Artist;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArtistMediaController extends Controller
{
    /**
     * GET /api/artists/{artist}/videos
     * Public — only published videos.
     */
    public function videos(Artist $artist): AnonymousResourceCollection
    {
        $videos = $artist->videos()
            ->published()
            ->ordered()
            ->get();

        return ArtistVideoResource::collection($videos);
    }

    /**
     * GET /api/artists/{artist}/gallery
     * Public — only published gallery images.
     */
    public function gallery(Artist $artist): AnonymousResourceCollection
    {
        $images = $artist->gallery()
            ->published()
            ->ordered()
            ->get();

        return ArtistGalleryImageResource::collection($images);
    }

    /**
     * GET /api/artists/{artist}/events
     * Public — only published events, split into upcoming/past.
     */
    public function events(Artist $artist): array
    {
        $upcoming = $artist->events()
            ->published()
            ->upcoming()
            ->get();

        $past = $artist->events()
            ->published()
            ->past()
            ->limit(20)
            ->get();

        return [
            'upcoming' => ArtistEventResource::collection($upcoming),
            'past' => ArtistEventResource::collection($past),
        ];
    }
}