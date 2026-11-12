<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
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
    public function storeRole(Request $request)
    {
        abort_unless($request->user()?->role === 'admin', 403);
        $data = $request->validate(['label' => 'required|string|max:100']);
        $name = \Illuminate\Support\Str::slug($data['label'], '_');
        abort_if(in_array($name, ['admin', 'customer'], true), 422, 'This role name is reserved.');
        \App\Models\Role::firstOrCreate(['name' => $name], ['label' => $data['label']]);
        \Illuminate\Support\Facades\Cache::flush();
        return redirect()->route('role_permissions.index')->with('status', 'Role created. Set its permissions below.');
    }

    /** Every role except 'admin', which always passes every permission check (see
     *  CheckPermission middleware); showing it would imply it can be restricted. */
    public function index(\Illuminate\Http\Request $request)
    {
        $permissions = Permission::orderBy('category')->orderBy('label')->get()->groupBy('category');

        $roles = array_values(array_filter(array_keys(User::roleOptions()), fn ($role) => $role !== 'admin'));
        $assigned = RolePermission::all()
            ->groupBy('role')
            ->map(fn ($rows) => $rows->pluck('permission_key')->all());

        return view('role_permissions.index', [
            'permissionGroups' => $permissions,
            'roles' => $roles,
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

        $roles = array_values(array_filter(array_keys(User::roleOptions()), fn ($role) => $role !== 'admin'));
        DB::transaction(function () use ($selected, $roles) {
            foreach ($roles as $role) {
                RolePermission::where('role', $role)->delete();

                foreach ($selected[$role] ?? [] as $permissionKey) {
                    RolePermission::create(['role' => $role, 'permission_key' => $permissionKey]);
                }
            }
        });
        \Illuminate\Support\Facades\Cache::flush();

        return redirect()->route('role_permissions.index')->with('status', 'Role permissions updated successfully.');
    }
}
