<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Limits a route to signed-in customer accounts (no role counts as customer). */
class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_unless(($user->role ?: 'customer') === 'customer', 403);

        return $next($request);
    }
}
