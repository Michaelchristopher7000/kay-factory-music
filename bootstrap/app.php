<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Allow Sanctum to authenticate SPA requests via session cookies on API routes
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
        $middleware->statefulApi();
        $middleware->redirectGuestsTo(fn () => null);

        // ---------------------------------------------------------------
        // Trusted proxies (Phase 4A)
        // ---------------------------------------------------------------
        // Reads from TRUSTED_PROXIES env var (comma-separated IPs or CIDR).
        // Leave empty in local development.
        // Set in production ONLY if you're behind a load balancer, reverse
        // proxy (Nginx), or CDN (Cloudflare).
        // Example: TRUSTED_PROXIES=192.168.1.1,10.0.0.0/8
        // Use '*' to trust all proxies (only if your app is NEVER directly
        // reachable from the internet).
        $trustedProxies = env('TRUSTED_PROXIES');

        if (! empty($trustedProxies)) {
            $trimmed = trim((string) $trustedProxies);

            if ($trimmed === '*') {
                $middleware->trustProxies(at: '*');
            } else {
                $proxies = array_filter(
                    array_map('trim', explode(',', $trimmed))
                );
                if (! empty($proxies)) {
                    $middleware->trustProxies(at: $proxies);
                }
            }
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function ($request, $throwable) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();














   