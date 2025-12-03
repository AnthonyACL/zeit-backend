<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, $roles)
    {
        $roles = explode('|', $roles);
        \Log::info('ROLES DEL TOKEN', [
            'id' => Auth::user()?->id,
            'roles' => Auth::user()?->getRoleNames(),
        ]);
        if (! Auth::check() || ! Auth::user()->hasAnyRole($roles)) {
            return response()->json(['message' => 'Acceso denegado.'], 403);
        }

        return $next($request);
    }
}
