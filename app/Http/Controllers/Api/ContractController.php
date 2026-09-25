<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contract\StoreContractRequest;
use App\Http\Requests\Contract\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ContractController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Contract::class);

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $contracts = Contract::query()
            ->with(['artist:id,artist_code,name', 'createdBy:id,name,email'])
            ->search($request->input('search'))
            ->status($request->input('status'))
            ->type($request->input('type'))
            ->forArtist($request->input('artist_id'))
            ->orderByDesc('id')
            ->paginate($perPage);

        return ContractResource::collection($contracts);
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        Gate::authorize('create', Contract::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $contract = Contract::create($data);
        $contract->load(['artist:id,artist_code,name', 'createdBy:id,name,email']);

        return (new ContractResource($contract))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Contract $contract): ContractResource
    {
        Gate::authorize('view', $contract);

        $contract->load(['artist:id,artist_code,name', 'createdBy:id,name,email']);

        return new ContractResource($contract);
    }

    public function update(UpdateContractRequest $request, Contract $contract): ContractResource
    {
        Gate::authorize('update', $contract);

        $contract->update($request->validated());
        $contract->load(['artist:id,artist_code,name', 'createdBy:id,name,email']);

        return new ContractResource($contract);
    }

    public function destroy(Contract $contract): JsonResponse
    {
        Gate::authorize('delete', $contract);

        $contract->delete();

        return response()->json([
            'message' => 'Contract deleted successfully.',
        ]);
    }
}