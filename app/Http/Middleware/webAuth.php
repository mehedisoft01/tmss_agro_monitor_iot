<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class webAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('dealers')->check()) {
            return $next($request);
        }
        return redirect()->route('web.login');
    }
}
