<?php

namespace App\Http\Middleware;

use Closure;

class CheckPermission
{
    public function handle($request, Closure $next, $permission)
    {
        if (!$request->user()->hasPermission($permission)) {
            return redirect()->route('index')->with('error', 'No tienes permiso para acceder a esta página.');
        }

        return $next($request);
    }
}
