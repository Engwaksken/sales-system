<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NavPageSeeder extends Seeder
{    public function run(): void
    {
        $rows = [
            ['label' => 'Dashboard', 'url' => 'index.php', 'icon' => 'fa-tachometer-alt', 'parent_id' => null, 'sort_order' => '10', 'permission_key' => 'view_dashboard', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Orders', 'url' => 'orders.php', 'icon' => 'fa-shopping-cart', 'parent_id' => null, 'sort_order' => '20', 'permission_key' => 'manage_orders', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Products', 'url' => 'products.php', 'icon' => 'fa-box', 'parent_id' => null, 'sort_order' => '30', 'permission_key' => 'manage_products', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Stock', 'url' => 'stock.php', 'icon' => 'fa-warehouse', 'parent_id' => null, 'sort_order' => '40', 'permission_key' => 'manage_stock', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Customers', 'url' => 'customers.php', 'icon' => 'fa-users', 'parent_id' => null, 'sort_order' => '50', 'permission_key' => 'manage_customers', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Suppliers', 'url' => 'suppliers.php', 'icon' => 'fa-truck', 'parent_id' => null, 'sort_order' => '60', 'permission_key' => 'manage_suppliers', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Branches', 'url' => 'branches.php', 'icon' => 'fa-building', 'parent_id' => null, 'sort_order' => '70', 'permission_key' => 'manage_branches', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Point of Sales', 'url' => 'sales.php', 'icon' => 'fa-dollar-sign', 'parent_id' => null, 'sort_order' => '80', 'permission_key' => 'manage_sales', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Expenses', 'url' => 'expenses.php', 'icon' => 'fa-receipt', 'parent_id' => null, 'sort_order' => '90', 'permission_key' => 'manage_expenses', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Users', 'url' => 'users.php', 'icon' => 'fa-user-cog', 'parent_id' => null, 'sort_order' => '110', 'permission_key' => 'manage_users', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Settings', 'url' => 'settings.php', 'icon' => 'fa-gear', 'parent_id' => null, 'sort_order' => '120', 'permission_key' => 'manage_settings', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'eFRIS Settings', 'url' => 'efris-settings.php', 'icon' => 'fa-shield-halved', 'parent_id' => null, 'sort_order' => '125', 'permission_key' => 'manage_settings', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Permissions', 'url' => 'permissions.php', 'icon' => 'fa-lock', 'parent_id' => null, 'sort_order' => '130', 'permission_key' => 'manage_users', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Reports', 'url' => '#', 'icon' => 'fa-chart-bar', 'parent_id' => null, 'sort_order' => '100', 'permission_key' => 'view_reports', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Business Report', 'url' => 'business_report.php', 'icon' => 'fa-briefcase', 'parent_id' => '14', 'sort_order' => '1', 'permission_key' => 'view_business_report', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Sales Report', 'url' => 'sales_reports.php', 'icon' => 'fa-dollar-sign', 'parent_id' => '14', 'sort_order' => '2', 'permission_key' => 'view_sales_report', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Stock Report', 'url' => 'stock_report.php', 'icon' => 'fa-warehouse', 'parent_id' => '14', 'sort_order' => '3', 'permission_key' => 'view_stock_report', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Low Stock', 'url' => 'low_stock.php', 'icon' => 'fa-triangle-exclamation', 'parent_id' => '14', 'sort_order' => '4', 'permission_key' => 'view_low_stock_report', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
            ['label' => 'Expiry Report', 'url' => 'expiry_stock.php', 'icon' => 'fa-calendar-xmark', 'parent_id' => '14', 'sort_order' => '5', 'permission_key' => 'view_expiry_stock_report', 'is_active' => '1', 'created_at' => '2026-04-06 12:46:41'],
        ];

        \App\Models\NavPage::insert($rows);
    }
}
