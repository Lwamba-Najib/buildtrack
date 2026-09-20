<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Not authenticated at all -> nothing for us to check here.
        if (! $user) {
            return $next($request);
        }

        // Grab the token that authenticated this request (may be null).
        $token = method_exists($user, 'currentAccessToken')
            ? $user->currentAccessToken()
            : null;

        if (! $token) {
            return $next($request);
        }

        // COOKIE / SPA session auth -> TransientToken has no ->name.
        // The session cookie already represents the active session, so allow it.
        if ($token instanceof TransientToken) {
            return $next($request);
        }

        // REAL API token -> enforce single active session by comparing the
        // token's name suffix to the user's stored session_id.
        if ($token instanceof PersonalAccessToken) {
            $prefix    = 'authToken_';
            $tokenName = (string) ($token->name ?? '');

            if (str_starts_with($tokenName, $prefix)) {
                $sessionIdFromToken = substr($tokenName, strlen($prefix));

                if ($user->session_id && $sessionIdFromToken !== $user->session_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your session is no longer active. Please log in again.',
                    ], 401);
                }
            }
        }

        return $next($request);
    }
}