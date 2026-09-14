<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // 404 rather than 403: an unauthorised visitor learns nothing about
        // which admin routes exist.
        abort_unless($request->user()?->isAdmin(), 404);

        return $next($request);
    }
}
