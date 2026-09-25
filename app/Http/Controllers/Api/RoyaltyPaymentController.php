<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoyaltyPayment\StoreRoyaltyPaymentRequest;
use App\Http\Requests\RoyaltyPayment\UpdateRoyaltyPaymentRequest;
use App\Http\Resources\RoyaltyPaymentResource;
use App\Models\RoyaltyPayment;
use App\Models\RoyaltyStatement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class RoyaltyPaymentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', RoyaltyPayment::class);

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $payments = RoyaltyPayment::query()
            ->with([
                'statement:id,statement_code,status,currency',
                'artist:id,artist_code,name',
                'createdBy:id,name,email',
            ])
            ->search($request->input('search'))
            ->forStatement($request->input('statement_id'))
            ->forArtist($request->input('artist_id'))
            ->method($request->input('method'))
            ->currency($request->input('currency'))
            ->fromDate($request->input('from'))
            ->toDate($request->input('to'))
            ->orderByDesc('id')
            ->paginate($perPage);

        return RoyaltyPaymentResource::collection($payments);
    }

    public function store(StoreRoyaltyPaymentRequest $request): JsonResponse
    {
        Gate::authorize('create', RoyaltyPayment::class);

        $data = $request->validated();
        $data['currency'] = strtoupper($data['currency']);
        $data['created_by'] = $request->user()->id;

        $statement = RoyaltyStatement::findOrFail($data['statement_id']);
        $data['artist_id'] = $statement->artist_id;

        $payment = RoyaltyPayment::create($data);

        $statement->recomputeTotals();

        $payment->load([
            'statement:id,statement_code,status,currency',
            'artist:id,artist_code,name',
            'createdBy:id,name,email',
        ]);

        return (new RoyaltyPaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    public function show(RoyaltyPayment $royaltyPayment): RoyaltyPaymentResource
    {
        Gate::authorize('view', $royaltyPayment);

        $royaltyPayment->load([
            'statement:id,statement_code,status,currency',
            'artist:id,artist_code,name',
            'createdBy:id,name,email',
        ]);

        return new RoyaltyPaymentResource($royaltyPayment);
    }

    public function update(UpdateRoyaltyPaymentRequest $request, RoyaltyPayment $royaltyPayment): RoyaltyPaymentResource
    {
        Gate::authorize('update', $royaltyPayment);

        $royaltyPayment->update($request->validated());

        $royaltyPayment->statement?->recomputeTotals();

        $royaltyPayment->load([
            'statement:id,statement_code,status,currency',
            'artist:id,artist_code,name',
            'createdBy:id,name,email',
        ]);

        return new RoyaltyPaymentResource($royaltyPayment);
    }

    public function destroy(RoyaltyPayment $royaltyPayment): JsonResponse
    {
        Gate::authorize('delete', $royaltyPayment);

        $statement = $royaltyPayment->statement;

        $royaltyPayment->delete();

        $statement?->recomputeTotals();

        return response()->json([
            'message' => 'Royalty payment deleted successfully.',
        ]);
    }
}