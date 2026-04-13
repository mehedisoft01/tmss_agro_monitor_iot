<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Auth\AuthenticationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
    web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JwtMiddleware::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {


        $exceptions->render(function (TokenExpiredException $e, $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Expired-',
                'error_code' => 'AUTH_ERROR',
            ], 401);
        });


        $exceptions->render(function (TokenInvalidException $e, $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token Invalid -',
                'error_code' => 'AUTH_ERROR',
            ], 401);
        });

        $exceptions->render(function (JWTException $e, $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'JWTException error',
                'error_code' => 'AUTH_ERROR',
            ], 401);
        });


        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated-',
                'error_code' => 'AUTH_ERROR',
            ], 401);
        });


//        $exceptions->render(function (Exception $e, $request) {
//            $errorMsg = app()->environment('production') ? 'Server Error' : $e->getMessage();
//            return returnData(5000, null, $errorMsg);
//        });


    })

    ->create();
