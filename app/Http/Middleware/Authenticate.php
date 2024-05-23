<?php

namespace App\Http\Middleware;

use App\Models\UserToken;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // get token in header
        $token = $request->header('Authorization');

        // check token in table 
        $userToken = UserToken::where('usr_token', $token)->first();

        // if token found, next the request
        if ($userToken) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
