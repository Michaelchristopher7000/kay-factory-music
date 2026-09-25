<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RevenueEntry\StoreRevenueEntryRequest;
use App\Http\Requests\RevenueEntry\UpdateRevenueEntryRequest;
use App\Http\Resources\RevenueEntryResource;
use App\Models\RevenueEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class RevenueEntryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', RevenueEntry::class);

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $entries = RevenueEntry::query()
            ->with([
                'artist:id,artist_code,name',
                'release:id,release_code,title',
                'track:id,track_code,title',
                'distribution:id,distribution_code,platform',
                'createdBy:id,name,email',
            ])
            ->search($request->input('search'))
            ->source($request->input('source'))
            ->platform($request->input('platform'))
            ->forArtist($request->input('artist_id'))
            ->forRelease($request->input('release_id'))
            ->forDistribution($request->input('distribution_id'))
            ->currency($request->input('currency'))
            ->fromDate($request->input('from'))
            ->toDate($request->input('to'))
            ->orderByDesc('id')
            ->paginate($perPage);

        return RevenueEntryResource::collection($entries);
    }

    public function store(StoreRevenueEntryRequest $request): JsonResponse
    {
        Gate::authorize('create', RevenueEntry::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $entry = RevenueEntry::create($data);
        $entry->load([
            'artist:id,artist_code,name',
            'release:id,release_code,title',
            'track:id,track_code,title',
            'distribution:id,distribution_code,platform',
            'createdBy:id,name,email',
        ]);

        return (new RevenueEntryResource($entry))
            ->response()
            ->setStatusCode(201);
    }

    public function show(RevenueEntry $revenueEntry): RevenueEntryResource
    {
        Gate::authorize('view', $revenueEntry);

        $revenueEntry->load([
            'artist:id,artist_code,name',
            'release:id,release_code,title',
            'track:id,track_code,title',
            'distribution:id,distribution_code,platform',
            'createdBy:id,name,email',
        ]);

        return new RevenueEntryResource($revenueEntry);
    }

    public function update(UpdateRevenueEntryRequest $request, RevenueEntry $revenueEntry): RevenueEntryResource
    {
        Gate::authorize('update', $revenueEntry);

        $revenueEntry->update($request->validated());
        $revenueEntry->load([
            'artist:id,artist_code,name',
            'release:id,release_code,title',
            'track:id,track_code,title',
            'distribution:id,distribution_code,platform',
            'createdBy:id,name,email',
        ]);

        return new RevenueEntryResource($revenueEntry);
    }

    public function destroy(RevenueEntry $revenueEntry): JsonResponse
    {
        Gate::authorize('delete', $revenueEntry);

        $revenueEntry->delete();

        return response()->json([
            'message' => 'Revenue entry deleted successfully.',
        ]);
    }
}