<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AuditLog::class);

        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->search($request->input('search'))
            ->forUser($request->input('user_id'))
            ->action($request->input('action'))
            ->modelType($request->input('model_type'))
            ->forModel($request->input('model_id'))
            ->fromDate($request->input('from'))
            ->toDate($request->input('to'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return AuditLogResource::collection($logs);
    }

    public function show(AuditLog $auditLog): AuditLogResource
    {
        Gate::authorize('view', $auditLog);

        $auditLog->load('user:id,name,email');

        return new AuditLogResource($auditLog);
    }
}