<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\TokenBlacklist; // Import your TokenBlacklist model

class AuthenticateJobSeeker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the Bearer token from the Authorization header
        $token = $request->bearerToken();


        // Check if the token is blacklisted
        if ($token && TokenBlacklist::where('token', $token)->exists()) {
            return response()->json([], 401);
        }

        // Check if the user is authenticated as JobSeeker
        if (!Auth::guard('job_seeker')->check()) {
            return response()->json([], 401);
        }

        return $next($request);
    }
}
