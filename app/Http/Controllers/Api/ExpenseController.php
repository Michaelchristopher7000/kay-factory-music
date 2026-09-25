<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ExpenseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Expense::class);

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $expenses = Expense::query()
            ->with([
                'artist:id,artist_code,name',
                'release:id,release_code,title',
                'track:id,track_code,title',
                'distribution:id,distribution_code,platform',
                'createdBy:id,name,email',
            ])
            ->search($request->input('search'))
            ->category($request->input('category'))
            ->forArtist($request->input('artist_id'))
            ->forRelease($request->input('release_id'))
            ->forDistribution($request->input('distribution_id'))
            ->currency($request->input('currency'))
            ->fromDate($request->input('from'))
            ->toDate($request->input('to'))
            ->orderByDesc('id')
            ->paginate($perPage);

        return ExpenseResource::collection($expenses);
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        Gate::authorize('create', Expense::class);

        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $expense = Expense::create($data);
        $expense->load([
            'artist:id,artist_code,name',
            'release:id,release_code,title',
            'track:id,track_code,title',
            'distribution:id,distribution_code,platform',
            'createdBy:id,name,email',
        ]);

        return (new ExpenseResource($expense))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Expense $expense): ExpenseResource
    {
        Gate::authorize('view', $expense);

        $expense->load([
            'artist:id,artist_code,name',
            'release:id,release_code,title',
            'track:id,track_code,title',
            'distribution:id,distribution_code,platform',
            'createdBy:id,name,email',
        ]);

        return new ExpenseResource($expense);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): ExpenseResource
    {
        Gate::authorize('update', $expense);

        $expense->update($request->validated());
        $expense->load([
            'artist:id,artist_code,name',
            'release:id,release_code,title',
            'track:id,track_code,title',
            'distribution:id,distribution_code,platform',
            'createdBy:id,name,email',
        ]);

        return new ExpenseResource($expense);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        Gate::authorize('delete', $expense);

        $expense->delete();

        return response()->json([
            'message' => 'Expense deleted successfully.',
        ]);
    }
}