<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TrackLastActivity
{
    public function handle(Request $request, Closure $next)
    {
        // Store the current time as the last activity time in the session
        Session::put('last_activity', time());

        return $next($request);
    }
}