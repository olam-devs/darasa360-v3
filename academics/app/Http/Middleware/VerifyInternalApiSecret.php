<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Guards server-to-server endpoints (routes/internal_api.php) called by the
 * Finance app - not a browser-facing auth mechanism, no session/CSRF
 * involved. Both apps share the same secret via INTERNAL_API_SECRET in
 * their .env.
 */
class VerifyInternalApiSecret
{
    public function handle(Request $request, Closure $next)
    {
        $expected = config('services.internal_api.secret');

        if (empty($expected) || !hash_equals($expected, (string) $request->header('X-Internal-Api-Secret'))) {
            abort(403, 'Invalid internal API secret.');
        }

        return $next($request);
    }
}
