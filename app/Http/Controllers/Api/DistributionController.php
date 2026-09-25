<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Distribution\StoreDistributionRequest;
use App\Http\Requests\Distribution\UpdateDistributionRequest;
use App\Http\Resources\DistributionResource;
use App\Models\Distribution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DistributionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Distribution::class);

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $distributions = Distribution::query()
            ->with(['release:id,release_code,title', 'createdBy:id,name,email'])
            ->search($request->input('search'))
            ->forRelease($request->input('release_id'))
            ->platform($request->input('platform'))
            ->status($request->input('status'))
            ->distributor($request->input('distributor'))
            ->orderByDesc('id')
            ->paginate($perPage);

        return DistributionResource::collection($distributions);
    }

    public function store(StoreDistributionRequest $request): JsonResponse
    {
        Gate::authorize('create', Distribution::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $distribution = Distribution::create($data);
        $distribution->load(['release:id,release_code,title', 'createdBy:id,name,email']);

        return (new DistributionResource($distribution))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Distribution $distribution): DistributionResource
    {
        Gate::authorize('view', $distribution);

        $distribution->load(['release:id,release_code,title', 'createdBy:id,name,email']);

        return new DistributionResource($distribution);
    }

    public function update(UpdateDistributionRequest $request, Distribution $distribution): DistributionResource
    {
        Gate::authorize('update', $distribution);

        $distribution->update($request->validated());
        $distribution->load(['release:id,release_code,title', 'createdBy:id,name,email']);

        return new DistributionResource($distribution);
    }

    public function destroy(Distribution $distribution): JsonResponse
    {
        Gate::authorize('delete', $distribution);

        $distribution->delete();

        return response()->json([
            'message' => 'Distribution deleted successfully.',
        ]);
    }
}