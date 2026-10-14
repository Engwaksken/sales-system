<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the generic key-value CRUD the `role_permissions` table would otherwise
 * get with a proper checkbox matrix: permissions down the side (grouped by
 * category), roles across the top. Ticking a box grants that role the permission;
 * unticking revokes it. One submit syncs everything in a single transaction.
 */
class RolePermissionsController extends Controller
{
    /** Matches the roles enum on the users table. 'admin' is intentionally excluded —
     *  it always passes every permission check (see CheckPermission middleware) and
     *  showing it here would incorrectly imply it can be restricted. */
    protected array $roles = ['manager', 'cashier', 'customer'];

    public function index(\Illuminate\Http\Request $request)
    {
        $permissions = Permission::orderBy('category')->orderBy('label')->get()->groupBy('category');

        $assigned = RolePermission::all()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('permission_key')->all());

        return view('role_permissions.index', [
            'permissionGroups' => $permissions,
            'roles' => $this->roles,
            'assigned' => $assigned,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'array',
            'permissions.*.*' => 'string',
        ]);

        $selected = $data['permissions'] ?? [];

        DB::transaction(function () use ($selected) {
            foreach ($this->roles as $role) {
                RolePermission::where('role', $role)->delete();

                foreach ($selected[$role] ?? [] as $permissionKey) {
                    RolePermission::create(['role' => $role, 'permission_key' => $permissionKey]);
                }
            }
        });

        return redirect()->route('role_permissions.index')->with('status', 'Role permissions updated successfully.');
    }
}
