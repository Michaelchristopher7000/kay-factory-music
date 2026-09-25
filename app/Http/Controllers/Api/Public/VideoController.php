<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistVideoResource;
use App\Models\ArtistVideo;
use Illuminate\Http\JsonResponse;

class VideoController extends Controller
{
    public function featured(): JsonResponse
    {
        $video = ArtistVideo::homeFeatured();

        if (! $video) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => array_merge(
                (new ArtistVideoResource($video))->resolve(),
                [
                    'artist' => $video->artist ? [
                        'id' => $video->artist->id,
                        'slug' => $video->artist->slug,
                        'name' => $video->artist->name,
                        'avatar' => $video->artist->avatar,
                        'genre' => $video->artist->genre,
                    ] : null,
                ],
            ),
        ]);
    }
}