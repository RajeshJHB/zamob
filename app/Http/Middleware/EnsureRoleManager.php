<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleManager
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isRoleManager()) {
            abort(403, 'Unauthorized. Only Role Managers can access this page.');
        }

        return $next($request);
    }
}
