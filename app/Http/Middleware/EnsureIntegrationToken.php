<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the `/api/integrations` routes with the shared n8n bearer token
 * (config/integrations.php). Compared in constant time; with no token
 * configured the routes are closed rather than open.
 */
class EnsureIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('integrations.n8n.token');
        $provided = $request->bearerToken();

        if (! is_string($expected) || $expected === '' || $provided === null || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
