<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Restrict a route to administrators only (PRD §5.2).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Unauthorized. This area is restricted to Administrators.');
        }

        if ($user->status !== 'active') {
            abort(403, 'Your account is not active. Please contact the administrator.');
        }

        return $next($request);
    }
}
