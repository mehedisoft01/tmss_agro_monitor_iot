<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $httpMethod = request()->method();

        if (in_array($httpMethod, ['POST', 'PUT', 'DELETE'])){
            $route = $request->route();
            $actionName = $route ? $route->getActionName() : null;
            $routeName  = $route ? $route->getName() : null;
            [$controller, $method] = $actionName ? explode('@', $actionName) : [null, null];


            $activity = new ActivityLog();
            $activity->fill([
                'user_id'     => auth()->check() ? auth()->user()->id : 0,
                'action'      => $method,
                'controller'  => class_basename($controller),
                'method'      => $httpMethod,
                'route_name'  => $routeName,
                'request_data'=> json_encode($request->except(['password','password_confirmation'])),
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
            ]);
            $activity->save();
        }

        return $response;
    }
}
