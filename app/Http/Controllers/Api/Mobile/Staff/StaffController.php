<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckPermission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared rules for the mobile staff app.
 *
 * Permissions mirror the website (role_permissions; admins pass everything).
 * Branches follow the existing mobile rule: admins work across all branches and
 * may pick one, everyone else is pinned to the branch on their account.
 */
abstract class StaffController extends Controller
{
    protected function authorizePermission(Request $request, string $permission): void
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_if(($user->role ?: 'customer') === 'customer', 403);
        abort_unless(CheckPermission::allows($user, $permission), 403, 'You do not have permission to access this module.');
    }

    protected function isAdmin(Request $request): bool
    {
        return $request->user()?->role === 'admin';
    }

    /** The branch a list is limited to: an admin's chosen branch (or all), else the user's own. */
    protected function branchFilter(Request $request): ?int
    {
        if ($this->isAdmin($request)) {
            return $request->filled('branch_id') ? $request->integer('branch_id') : null;
        }

        return $this->ownBranch($request);
    }

    /** The branch a new record goes to: an admin's choice (required), else the user's own. */
    protected function writeBranch(Request $request, mixed $requested): int
    {
        if ($this->isAdmin($request)) {
            abort_unless($requested, 422, 'Choose a branch.');

            return (int) $requested;
        }

        return $this->ownBranch($request);
    }

    protected function ownBranch(Request $request): int
    {
        $branchId = $request->user()->branch_id;
        abort_unless($branchId, 403, 'Your account is not assigned to a branch. Ask an admin to set one.');

        return (int) $branchId;
    }

    /** Limits a query to the request's branch when one applies. */
    protected function scopeBranch(Builder $query, Request $request, string $column = 'branch_id'): Builder
    {
        $branchId = $this->branchFilter($request);

        return $branchId ? $query->where($column, $branchId) : $query;
    }

    protected function meta(LengthAwarePaginator $page): array
    {
        return [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
        ];
    }

    protected function money(mixed $value): float
    {
        return round((float) $value, 2);
    }
}
