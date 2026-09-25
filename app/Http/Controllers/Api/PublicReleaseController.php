<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicReleaseResource;
use App\Models\Release;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicReleaseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min((int) $request->input('per_page', 12), 100));

        $releases = Release::query()
            ->publicVisible()
            ->with(['artist:id,slug,name,genre,avatar'])
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('artist_slug'), function ($q, $slug) {
                $q->whereHas('artist', fn ($aq) => $aq->where('slug', $slug));
            })
            ->orderByDesc('release_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return PublicReleaseResource::collection($releases);
    }

    public function show(Release $release): PublicReleaseResource
    {
        abort_unless($release->status === Release::STATUS_RELEASED, 404);

        $release->load([
            'artist:id,slug,name,genre,avatar',
            'tracks:id,title,duration_seconds,is_explicit',
        ]);

        return new PublicReleaseResource($release);
    }
}