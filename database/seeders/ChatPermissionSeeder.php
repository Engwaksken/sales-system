<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

/**
 * The customer-chat widget is new in this Laravel port (not part of the original
 * app), so its permission doesn't exist in the SQL-dump-derived PermissionSeeder/
 * RolePermissionSeeder. Seeded here instead, granted to every staff role except
 * 'customer' (which never sees the admin panel) — 'admin' doesn't need a row since
 * it always passes every permission check regardless.
 */
class ChatPermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::updateOrCreate(
            ['permission_key' => 'manage_chats'],
            [
                'label' => 'Manage Customer Chats',
                'category' => 'Support',
                'description' => 'Reply to customers via the storefront chat widget',
            ]
        );

        foreach (['manager', 'cashier'] as $role) {
            RolePermission::firstOrCreate(['role' => $role, 'permission_key' => 'manage_chats']);
        }
    }
}
