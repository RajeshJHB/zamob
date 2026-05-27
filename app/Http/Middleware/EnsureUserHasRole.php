<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->hasAnyRole()) {
            if ($request->expectsJson()) {
                abort(403, 'You do not have any roles assigned.');
            }

            return redirect()
                ->route('profile.show')
                ->with('error', 'You do not have any roles assigned. Please contact an administrator to be assigned roles.');
        }

        return $next($request);
    }
}
