<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            // Get the current token's session ID from the token name
            $currentToken = $user->currentAccessToken();
            $tokenName = $currentToken ? $currentToken->name : '';
            $tokenSessionId = str_replace('authToken_', '', $tokenName);

            // If the stored session ID doesn't match, force logout
            if ($user->session_id !== $tokenSessionId) {
                $user->tokens()->delete();
                Auth::logout();

                return response()->json([
                    'success' => false,
                    'message' => 'You have been logged out because you logged in from another device.',
                ], 401);
            }
        }

        return $next($request);
    }
}
