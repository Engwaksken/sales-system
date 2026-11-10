<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the General Manager, Marketing Manager and Accountant roles, each with a
 * sensible starting set of permissions that admins can adjust on the Role
 * Permissions page.
 */
return new class extends Migration
{
    private const OLD_ROLES = ['admin', 'cashier', 'customer', 'manager'];
    private const NEW_ROLES = ['general_manager', 'marketing_manager', 'accountant'];

    private const DEFAULT_PERMISSIONS = [
        'general_manager' => [
            'view_dashboard', 'manage_sales', 'manage_orders', 'manage_customers', 'customer_statement',
            'manage_statements', 'manage_products', 'manage_categories', 'manage_stock', 'manage_suppliers',
            'manage_expenses', 'manage_branches', 'manage_notifications', 'manage_chats', 'view_reports',
            'view_sales_report', 'view_profit_report', 'view_business_report', 'view_stock_report',
            'view_low_stock_report', 'view_expiry_stock_report',
        ],
        'marketing_manager' => [
            'view_dashboard', 'manage_customers', 'manage_orders', 'manage_products', 'manage_categories',
            'manage_notifications', 'manage_chats', 'view_reports', 'view_sales_report',
        ],
        'accountant' => [
            'view_dashboard', 'manage_sales', 'manage_expenses', 'manage_suppliers', 'customer_statement',
            'manage_statements', 'view_reports', 'view_sales_report', 'view_profit_report',
            'view_business_report', 'view_stock_report',
        ],
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [...self::OLD_ROLES, ...self::NEW_ROLES])->default('cashier')->change();
        });

        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        $now = now();
        foreach (self::DEFAULT_PERMISSIONS as $role => $keys) {
            foreach ($keys as $key) {
                $exists = DB::table('role_permissions')->where('role', $role)->where('permission_key', $key)->exists();
                if (! $exists) {
                    DB::table('role_permissions')->insert([
                        'role' => $role, 'permission_key' => $key, 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('users')->whereIn('role', self::NEW_ROLES)->update(['role' => 'manager']);

        if (Schema::hasTable('role_permissions')) {
            DB::table('role_permissions')->whereIn('role', self::NEW_ROLES)->delete();
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', self::OLD_ROLES)->default('cashier')->change();
        });
    }
};
