<?php

namespace App\Http\Middleware;

use App\Models\RolePermission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mirrors the legacy functions_acl.php `can($action)` check: a role is allowed
 * to hit a module if there is a matching row in role_permissions, unless the
 * role is 'admin' (which is always allowed, matching the original admin superuser behaviour).
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! self::allows($user, $permissionKey)) {
            abort(403, 'You do not have permission to access this module.');
        }

        return $next($request);
    }

    /** Same rule as the route middleware, for checks made inside controllers and views. */
    public static function allows($user, string $permissionKey): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return Cache::remember(
            "role_perm:{$user->role}:{$permissionKey}",
            60,
            fn () => RolePermission::where('role', $user->role)
                ->where('permission_key', $permissionKey)
                ->exists()
        );
    }
}
