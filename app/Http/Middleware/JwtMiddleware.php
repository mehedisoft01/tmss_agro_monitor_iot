<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;


class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        JWTAuth::parseToken()->authenticate();
        return $next($request);
    }


}
