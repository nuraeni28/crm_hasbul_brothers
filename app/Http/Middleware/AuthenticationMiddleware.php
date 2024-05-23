<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $method)
    {
          // Cek metode HTTP
        if ($request->method() !== strtoupper($method)) {
            return response()->json(['error' => 'Method not allowed.'], 405);
        }

        // Cek otorisasi (pastikan user sudah login)
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        
        return $next($request);
    }
}
