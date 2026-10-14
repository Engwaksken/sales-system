<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{    public function run(): void
    {
        $rows = [
            ['role' => 'admin', 'permission_key' => 'customer_statement'],
            ['role' => 'admin', 'permission_key' => 'manage_branches'],
            ['role' => 'admin', 'permission_key' => 'manage_categories'],
            ['role' => 'admin', 'permission_key' => 'manage_customers'],
            ['role' => 'admin', 'permission_key' => 'manage_expenses'],
            ['role' => 'admin', 'permission_key' => 'manage_notifications'],
            ['role' => 'admin', 'permission_key' => 'manage_orders'],
            ['role' => 'admin', 'permission_key' => 'manage_products'],
            ['role' => 'admin', 'permission_key' => 'manage_sales'],
            ['role' => 'admin', 'permission_key' => 'manage_settings'],
            ['role' => 'admin', 'permission_key' => 'manage_statements'],
            ['role' => 'admin', 'permission_key' => 'manage_stock'],
            ['role' => 'admin', 'permission_key' => 'manage_suppliers'],
            ['role' => 'admin', 'permission_key' => 'manage_users'],
            ['role' => 'admin', 'permission_key' => 'view_business_report'],
            ['role' => 'admin', 'permission_key' => 'view_dashboard'],
            ['role' => 'admin', 'permission_key' => 'view_expiry_stock_report'],
            ['role' => 'admin', 'permission_key' => 'view_low_stock_report'],
            ['role' => 'admin', 'permission_key' => 'view_profit_report'],
            ['role' => 'admin', 'permission_key' => 'view_reports'],
            ['role' => 'admin', 'permission_key' => 'view_sales_report'],
            ['role' => 'admin', 'permission_key' => 'view_stock_report'],
            ['role' => 'cashier', 'permission_key' => 'manage_notifications'],
            ['role' => 'cashier', 'permission_key' => 'manage_orders'],
            ['role' => 'cashier', 'permission_key' => 'manage_sales'],
            ['role' => 'cashier', 'permission_key' => 'view_dashboard'],
            ['role' => 'manager', 'permission_key' => 'customer_statement'],
            ['role' => 'manager', 'permission_key' => 'manage_categories'],
            ['role' => 'manager', 'permission_key' => 'manage_customers'],
            ['role' => 'manager', 'permission_key' => 'manage_expenses'],
            ['role' => 'manager', 'permission_key' => 'manage_notifications'],
            ['role' => 'manager', 'permission_key' => 'manage_orders'],
            ['role' => 'manager', 'permission_key' => 'manage_products'],
            ['role' => 'manager', 'permission_key' => 'manage_sales'],
            ['role' => 'manager', 'permission_key' => 'manage_stock'],
            ['role' => 'manager', 'permission_key' => 'manage_suppliers'],
            ['role' => 'manager', 'permission_key' => 'view_business_report'],
            ['role' => 'manager', 'permission_key' => 'view_dashboard'],
            ['role' => 'manager', 'permission_key' => 'view_expiry_stock_report'],
            ['role' => 'manager', 'permission_key' => 'view_low_stock_report'],
            ['role' => 'manager', 'permission_key' => 'view_profit_report'],
            ['role' => 'manager', 'permission_key' => 'view_reports'],
            ['role' => 'manager', 'permission_key' => 'view_sales_report'],
            ['role' => 'manager', 'permission_key' => 'view_stock_report'],
        ];

        \App\Models\RolePermission::insert($rows);
    }
}
