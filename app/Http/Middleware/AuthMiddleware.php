<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            App::setLocale(auth()->user()->locale);

            return $next($request);
        }
        return redirect(route('login'));
    }
}
