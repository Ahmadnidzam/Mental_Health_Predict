<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Hanya izinkan user dengan flag admin; selain itu 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->user()?->isAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
