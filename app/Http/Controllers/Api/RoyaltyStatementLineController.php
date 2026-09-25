<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoyaltyStatement\UpdateRoyaltyStatementLineRequest;
use App\Http\Resources\RoyaltyStatementLineResource;
use App\Models\RoyaltyStatementLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class RoyaltyStatementLineController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', RoyaltyStatementLine::class);

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $lines = RoyaltyStatementLine::query()
            ->with(['release:id,release_code,title', 'track:id,track_code,title'])
            ->forStatement($request->input('statement_id'))
            ->source($request->input('source'))
            ->forRelease($request->input('release_id'))
            ->forTrack($request->input('track_id'))
            ->orderByDesc('id')
            ->paginate($perPage);

        return RoyaltyStatementLineResource::collection($lines);
    }

    public function update(UpdateRoyaltyStatementLineRequest $request, RoyaltyStatementLine $royaltyStatementLine): RoyaltyStatementLineResource
    {
        Gate::authorize('update', $royaltyStatementLine);

        $data = $request->validated();

        // If rate changes and royalty_amount is not supplied, recompute it.
        if (isset($data['royalty_rate']) && ! isset($data['royalty_amount'])) {
            $data['royalty_amount'] = round(
                (float) $royaltyStatementLine->revenue_amount * (float) $data['royalty_rate'] / 100,
                2
            );
        }

        $royaltyStatementLine->update($data);

        // Recompute parent totals.
        $royaltyStatementLine->statement?->recomputeTotals();

        $royaltyStatementLine->load([
            'release:id,release_code,title',
            'track:id,track_code,title',
        ]);

        return new RoyaltyStatementLineResource($royaltyStatementLine);
    }

    public function destroy(RoyaltyStatementLine $royaltyStatementLine): JsonResponse
    {
        Gate::authorize('delete', $royaltyStatementLine);

        $statement = $royaltyStatementLine->statement;

        $royaltyStatementLine->delete();

        $statement?->recomputeTotals();

        return response()->json([
            'message' => 'Royalty statement line deleted successfully.',
        ]);
    }
}