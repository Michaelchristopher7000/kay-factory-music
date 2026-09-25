<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoyaltyStatement\StoreRoyaltyStatementRequest;
use App\Http\Requests\RoyaltyStatement\UpdateRoyaltyStatementRequest;
use App\Http\Resources\RoyaltyStatementResource;
use App\Models\Contract;
use App\Models\RoyaltyStatement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class RoyaltyStatementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', RoyaltyStatement::class);

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $statements = RoyaltyStatement::query()
            ->with(['artist:id,artist_code,name', 'createdBy:id,name,email'])
            ->search($request->input('search'))
            ->forArtist($request->input('artist_id'))
            ->status($request->input('status'))
            ->currency($request->input('currency'))
            ->fromDate($request->input('from'))
            ->toDate($request->input('to'))
            ->orderByDesc('id')
            ->paginate($perPage);

        return RoyaltyStatementResource::collection($statements);
    }

    public function store(StoreRoyaltyStatementRequest $request): JsonResponse
    {
        Gate::authorize('create', RoyaltyStatement::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['currency'] = strtoupper($data['currency']);

        $generate = (bool) ($data['generate'] ?? true);
        unset($data['generate']);

        // Resolve rate — request wins, else latest active contract, else 0.
        $rate = $data['royalty_rate'] ?? null;
        $contractId = null;

        if ($rate === null) {
            $contract = Contract::query()
                ->where('artist_id', $data['artist_id'])
                ->where('status', Contract::STATUS_ACTIVE)
                ->orderByDesc('signed_date')
                ->orderByDesc('id')
                ->first();

            if ($contract && $contract->royalty_rate !== null) {
                $rate = (float) $contract->royalty_rate;
                $contractId = $contract->id;
            } else {
                $rate = 0;
            }
        } else {
            // If a rate is provided, still try to identify a reference contract.
            $contract = Contract::query()
                ->where('artist_id', $data['artist_id'])
                ->where('status', Contract::STATUS_ACTIVE)
                ->orderByDesc('signed_date')
                ->orderByDesc('id')
                ->first();

            $contractId = $contract?->id;
        }

        $data['royalty_rate'] = $rate;
        $data['status'] = RoyaltyStatement::STATUS_DRAFT;

        $statement = RoyaltyStatement::create($data);

        if ($generate) {
            $statement->generateLines($contractId);
        }

        $statement->load([
            'artist:id,artist_code,name',
            'createdBy:id,name,email',
            'lines.release:id,release_code,title',
            'lines.track:id,track_code,title',
        ]);

        return (new RoyaltyStatementResource($statement))
            ->response()
            ->setStatusCode(201);
    }

    public function show(RoyaltyStatement $royaltyStatement): RoyaltyStatementResource
    {
        Gate::authorize('view', $royaltyStatement);

        $royaltyStatement->load([
            'artist:id,artist_code,name',
            'createdBy:id,name,email',
            'lines.release:id,release_code,title',
            'lines.track:id,track_code,title',
        ]);

        return new RoyaltyStatementResource($royaltyStatement);
    }

    public function update(UpdateRoyaltyStatementRequest $request, RoyaltyStatement $royaltyStatement): RoyaltyStatementResource
    {
        Gate::authorize('update', $royaltyStatement);

        $data = $request->validated();

        $regenerate = (bool) ($data['regenerate'] ?? false);
        unset($data['regenerate']);

        $wasDraft = $royaltyStatement->status === RoyaltyStatement::STATUS_DRAFT;

        if (isset($data['status']) && $data['status'] === RoyaltyStatement::STATUS_ISSUED) {
            if (empty($data['issued_at'])) {
                $data['issued_at'] = now()->toDateString();
            }
        }

        $royaltyStatement->update($data);

        if ($regenerate && $wasDraft) {
            $royaltyStatement->generateLines();
        } else {
            $royaltyStatement->recomputeTotals();
        }

        $royaltyStatement->load([
            'artist:id,artist_code,name',
            'createdBy:id,name,email',
            'lines.release:id,release_code,title',
            'lines.track:id,track_code,title',
        ]);

        return new RoyaltyStatementResource($royaltyStatement);
    }

    public function destroy(RoyaltyStatement $royaltyStatement): JsonResponse
    {
        Gate::authorize('delete', $royaltyStatement);

        $royaltyStatement->delete();

        return response()->json([
            'message' => 'Royalty statement deleted successfully.',
        ]);
    }

    public function regenerate(RoyaltyStatement $royaltyStatement): RoyaltyStatementResource
    {
        Gate::authorize('regenerate', $royaltyStatement);

        if ($royaltyStatement->status !== RoyaltyStatement::STATUS_DRAFT) {
            abort(422, 'Only draft statements can be regenerated.');
        }

        $royaltyStatement->generateLines();

        $royaltyStatement->load([
            'artist:id,artist_code,name',
            'createdBy:id,name,email',
            'lines.release:id,release_code,title',
            'lines.track:id,track_code,title',
        ]);

        return new RoyaltyStatementResource($royaltyStatement);
    }
}