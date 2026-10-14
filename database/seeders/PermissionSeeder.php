<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
        // Uses updateOrInsert on 'permission_key' so the seeder is safe to re-run.
    public function run(): void
    {
        $rows = [
            ['permission_key' => 'view_dashboard', 'label' => 'View Dashboard', 'category' => 'Dashboard', 'description' => 'Access the main dashboard', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_sales', 'label' => 'Manage Sales / POS', 'category' => 'Sales', 'description' => 'Access Point of Sale and sales records', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_orders', 'label' => 'Manage Orders', 'category' => 'Sales', 'description' => 'View and process customer orders', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_expenses', 'label' => 'Manage Expenses', 'category' => 'Sales', 'description' => 'Record and view expenses', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'customer_statement', 'label' => 'Customer Statement', 'category' => 'Sales', 'description' => 'View per-customer account statements', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_products', 'label' => 'Manage Products', 'category' => 'Inventory', 'description' => 'Add, edit and delete products', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_stock', 'label' => 'Manage Stock', 'category' => 'Inventory', 'description' => 'Adjust stock levels and pricing', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_categories', 'label' => 'Manage Categories', 'category' => 'Inventory', 'description' => 'Create and edit product categories', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_customers', 'label' => 'Manage Customers', 'category' => 'People', 'description' => 'Add and edit customer records', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_suppliers', 'label' => 'Manage Suppliers', 'category' => 'People', 'description' => 'Add and edit supplier records', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_users', 'label' => 'Manage Users', 'category' => 'People', 'description' => 'Create and manage user accounts', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_branches', 'label' => 'Manage Branches', 'category' => 'Org', 'description' => 'Create and configure branches', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_settings', 'label' => 'Manage Settings', 'category' => 'Org', 'description' => 'System settings and configuration', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_notifications', 'label' => 'Manage Notifications', 'category' => 'Org', 'description' => 'View and dismiss system notifications', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'manage_statements', 'label' => 'Manage Statements', 'category' => 'Org', 'description' => 'Access financial statements', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'view_reports', 'label' => 'View Reports (menu)', 'category' => 'Reports', 'description' => 'See the Reports section in navigation', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'view_business_report', 'label' => 'Business Report', 'category' => 'Reports', 'description' => 'Full income / balance sheet', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'view_sales_report', 'label' => 'Sales Report', 'category' => 'Reports', 'description' => 'Sales trend and detail report', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'view_stock_report', 'label' => 'Stock Report', 'category' => 'Reports', 'description' => 'Stock levels report', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'view_low_stock_report', 'label' => 'Low Stock Report', 'category' => 'Reports', 'description' => 'Items below threshold', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'view_expiry_stock_report', 'label' => 'Expiry Stock Report', 'category' => 'Reports', 'description' => 'Items approaching expiry', 'created_at' => '2026-04-06 13:18:26'],
            ['permission_key' => 'view_profit_report', 'label' => 'Profit Report', 'category' => 'Reports', 'description' => 'Gross / net profit analysis', 'created_at' => '2026-04-06 13:18:26'],
        ];

        foreach ($rows as $row) {
            \App\Models\Permission::updateOrCreate(['permission_key' => $row['permission_key']], $row);
        }
    }
}
