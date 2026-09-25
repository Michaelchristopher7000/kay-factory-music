<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role) {
            return response()->json(['message' => 'Forbidden — no role assigned.'], 403);
        }

        // Super Admin always passes, regardless of which roles the route asks for
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (! in_array($user->role->slug, $roles, true)) {
            return response()->json(['message' => 'Forbidden — insufficient permissions.'], 403);
        }

        return $next($request);
    }
}