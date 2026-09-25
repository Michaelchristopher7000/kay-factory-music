<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Artist\StoreArtistVideoRequest;
use App\Http\Requests\Artist\UpdateArtistVideoRequest;
use App\Http\Resources\ArtistVideoResource;
use App\Models\ArtistVideo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ArtistVideoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $videos = ArtistVideo::query()
            ->when($request->input('artist_id'), fn ($q, $id) => $q->where('artist_id', $id))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('search'), fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->orderByDesc('id')
            ->paginate($perPage);

        return ArtistVideoResource::collection($videos);
    }

    public function store(StoreArtistVideoRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! empty($data['is_home_featured'])) {
            $this->clearOtherHomeFeatured();
        }

        if ($data['source'] === ArtistVideo::SOURCE_YOUTUBE) {
            $data['youtube_id'] = ArtistVideo::extractYoutubeId($data['youtube_url']);
            unset($data['youtube_url']);
        }

        if ($data['source'] === ArtistVideo::SOURCE_UPLOAD && $request->hasFile('video')) {
            $data['video_path'] = $request->file('video')
                ->store("artists/{$data['artist_id']}/videos", 'public');
        }
        unset($data['video']);

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_path'] = $request->file('thumbnail')
                ->store("artists/{$data['artist_id']}/video-thumbnails", 'public');
        }
        unset($data['thumbnail']);

        $video = ArtistVideo::create($data);

        return (new ArtistVideoResource($video))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ArtistVideo $video): ArtistVideoResource
    {
        return new ArtistVideoResource($video);
    }

    public function update(UpdateArtistVideoRequest $request, ArtistVideo $video): ArtistVideoResource
    {
        $data = $request->validated();

        if (array_key_exists('is_home_featured', $data) && $data['is_home_featured']) {
            $this->clearOtherHomeFeatured($video->id);
        }

        if (isset($data['source']) && $data['source'] === ArtistVideo::SOURCE_YOUTUBE) {
            if (! empty($data['youtube_url'])) {
                $data['youtube_id'] = ArtistVideo::extractYoutubeId($data['youtube_url']);
            }
            unset($data['youtube_url']);
        }

        if ($request->hasFile('video')) {
            if ($video->video_path) {
                Storage::disk('public')->delete($video->video_path);
            }
            $data['video_path'] = $request->file('video')
                ->store("artists/{$video->artist_id}/videos", 'public');
        }
        unset($data['video']);

        if ($request->hasFile('thumbnail')) {
            if ($video->thumbnail_path) {
                Storage::disk('public')->delete($video->thumbnail_path);
            }
            $data['thumbnail_path'] = $request->file('thumbnail')
                ->store("artists/{$video->artist_id}/video-thumbnails", 'public');
        }
        unset($data['thumbnail']);

        $video->update($data);

        return new ArtistVideoResource($video->fresh() ?? $video);
    }

    public function destroy(ArtistVideo $video): JsonResponse
    {
        if ($video->video_path) {
            Storage::disk('public')->delete($video->video_path);
        }
        if ($video->thumbnail_path) {
            Storage::disk('public')->delete($video->thumbnail_path);
        }

        $video->delete();

        return response()->json(['message' => 'Video deleted.']);
    }

    /**
     * Ensures only one video is featured on the home page at a time.
     */
    protected function clearOtherHomeFeatured(?int $exceptId = null): void
    {
        $query = ArtistVideo::where('is_home_featured', true);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_home_featured' => false]);
    }
}