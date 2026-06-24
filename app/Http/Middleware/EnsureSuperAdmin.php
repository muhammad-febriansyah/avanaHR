<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to platform super-admins.
 */
class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) ($request->user()?->getAttributes()['is_super_admin'] ?? false), 403);

        return $next($request);
    }
}
