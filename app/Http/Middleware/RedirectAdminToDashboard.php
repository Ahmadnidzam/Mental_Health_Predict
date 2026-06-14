<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectAdminToDashboard
{
    /**
     * Paksa admin tetap di area admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
