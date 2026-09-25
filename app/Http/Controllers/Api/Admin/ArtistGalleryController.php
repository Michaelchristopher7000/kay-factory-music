<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Artist\StoreArtistGalleryImageRequest;
use App\Http\Requests\Artist\UpdateArtistGalleryImageRequest;
use App\Http\Resources\ArtistGalleryImageResource;
use App\Models\ArtistGalleryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ArtistGalleryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = max(1, min((int) $request->input('per_page', 30), 100));

        $images = ArtistGalleryImage::query()
            ->when($request->input('artist_id'), fn ($q, $id) => $q->where('artist_id', $id))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage);

        return ArtistGalleryImageResource::collection($images);
    }

    public function store(StoreArtistGalleryImageRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['image_path'] = $request->file('image')
            ->store("artists/{$data['artist_id']}/gallery", 'public');

        unset($data['image']);

        $image = ArtistGalleryImage::create($data);

        return (new ArtistGalleryImageResource($image))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ArtistGalleryImage $image): ArtistGalleryImageResource
    {
        return new ArtistGalleryImageResource($image);
    }

    public function update(UpdateArtistGalleryImageRequest $request, ArtistGalleryImage $image): ArtistGalleryImageResource
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
            $data['image_path'] = $request->file('image')
                ->store("artists/{$image->artist_id}/gallery", 'public');
        }
        unset($data['image']);

        $image->update($data);

        return new ArtistGalleryImageResource($image->fresh());
    }

    public function destroy(ArtistGalleryImage $image): JsonResponse
    {
        if ($image->image_path) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return response()->json(['message' => 'Image deleted.']);
    }
}