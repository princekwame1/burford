<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /** Usage: ->middleware('role:admin,manager'). Administrators always pass. */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || (! $user->isAdmin() && ! $user->hasRole(...$roles))) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
