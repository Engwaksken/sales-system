<?php

namespace App\Support\Imports;

use App\Models\Branch;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\DeliveryZone;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ProductBranch;
use App\Models\Supplier;

/**
 * Pages that accept spreadsheet (CSV) uploads. Each column lists the same
 * validation rules as the page's own form, plus how names in the file are
 * turned into records (lookup) and which columns identify an existing record
 * (match) so re-uploading a file updates rows instead of duplicating them.
 *
 * Lookups: branch, branches (one, several "Kampala; Gulu" or "All Branches"),
 * supplier, category, product (name or SKU).
 *
 * Deliberately not importable: users (passwords), sales/orders/payments
 * (they run stock, receipt and accounting logic), EFRIS, gateways, email
 * settings, permissions and log pages.
 */
class ImportModules
{
    public static function all(): array
    {
        return [
            'branches' => [
                'label' => 'Branches',
                'model' => Branch::class,
                'permission' => 'manage_branches',
                'index' => 'branches.index',
                'match' => ['name'],
                'columns' => [
                    'name' => ['rules' => 'required|string|max:255', 'example' => 'Gulu Branch'],
                    'location' => ['rules' => 'nullable|string|max:255', 'example' => 'Gulu City'],
                    'contact' => ['rules' => 'nullable|string|max:50', 'example' => '+256700000000'],
                    'branch_email' => ['rules' => 'nullable|email|max:255', 'example' => 'gulu@example.com'],
                ],
            ],
            'categories' => [
                'label' => 'Categories',
                'model' => Category::class,
                'permission' => 'manage_categories',
                'index' => 'categories.index',
                'match' => ['category_name'],
                'columns' => [
                    'category_name' => ['rules' => 'required|string|max:255', 'example' => 'Beverages'],
                    'description' => ['rules' => 'nullable|string', 'example' => 'Soft drinks and juices'],
                    'status' => ['rules' => 'required|in:active,inactive', 'default' => 'active', 'example' => 'active'],
                ],
            ],
            'suppliers' => [
                'label' => 'Suppliers',
                'model' => Supplier::class,
                'permission' => 'manage_suppliers',
                'index' => 'suppliers.index',
                'match' => ['name'],
                'columns' => [
                    'name' => ['rules' => 'required|string|max:255', 'example' => 'Acme Distributors'],
                    'contact_name' => ['rules' => 'required|string|max:255', 'example' => 'Jane Doe'],
                    'email' => ['rules' => 'nullable|email|max:255', 'example' => 'orders@acme.example'],
                    'phone' => ['rules' => 'nullable|string|max:50', 'example' => '+256700000001'],
                    'address' => ['rules' => 'nullable|string', 'example' => 'Plot 1, Kampala Road'],
                    'branch' => ['field' => 'branch_id', 'lookup' => 'branch', 'rules' => 'nullable', 'example' => 'Main Branch'],
                    'status' => ['rules' => 'required|in:active,inactive', 'default' => 'active', 'example' => 'active'],
                ],
            ],
            'stock' => [
                'label' => 'Stock (restock)',
                'model' => \App\Models\Stock::class,
                'permission' => 'manage_stock',
                'index' => 'stock.index',
                'handler' => 'stock',
                'note' => 'Quantities are ADDED to the stock already in each branch. Prices, unit and dates replace the current values when filled in. A branch with no stock of the product yet needs both prices.',
                'columns' => [
                    'product' => ['field' => 'product_id', 'lookup' => 'product', 'rules' => 'required', 'example' => 'Example Product', 'help' => 'Product name or SKU'],
                    'branches' => ['field' => 'branch_ids', 'lookup' => 'branches', 'rules' => 'required', 'example' => 'Main Branch; Gulu Branch', 'help' => 'One branch, several separated by ";" or "All Branches"'],
                    'quantity' => ['rules' => 'required|numeric|gt:0', 'example' => '50'],
                    'cost_price' => ['rules' => 'nullable|numeric|min:0', 'example' => '5000'],
                    'selling_price' => ['rules' => 'nullable|numeric|min:0', 'example' => '7500'],
                    'supplier' => ['field' => 'supplier_id', 'lookup' => 'supplier', 'rules' => 'nullable', 'example' => ''],
                    'unit_type' => ['rules' => 'nullable|string|max:50', 'example' => 'pcs'],
                    'expiry_date' => ['rules' => 'nullable|date', 'type' => 'date', 'example' => ''],
                    'stock_date' => ['rules' => 'nullable|date', 'type' => 'date', 'example' => ''],
                ],
            ],
            'expense_categories' => [
                'label' => 'Expense Categories',
                'model' => ExpenseCategory::class,
                'permission' => 'manage_expenses',
                'index' => 'expense_categories.index',
                'match' => ['name'],
                'columns' => [
                    'name' => ['rules' => 'required|string|max:255', 'example' => 'Transport'],
                    'status' => ['rules' => 'boolean', 'type' => 'boolean', 'default' => '1', 'example' => 'yes'],
                ],
            ],
            'expenses' => [
                'label' => 'Expenses',
                'model' => Expense::class,
                'permission' => 'manage_expenses',
                'index' => 'expenses.index',
                'note' => 'Every row creates a new expense, so upload each file only once.',
                'columns' => [
                    'title' => ['rules' => 'required|string|max:255', 'example' => 'Fuel for delivery van'],
                    'amount' => ['rules' => 'required|numeric|min:0', 'example' => '150000'],
                    'expense_date' => ['rules' => 'required|date', 'type' => 'date', 'example' => '2026-08-15'],
                    'category' => ['rules' => 'required|string|max:150', 'example' => 'Transport'],
                    'branch' => ['field' => 'branch_id', 'lookup' => 'branch', 'rules' => 'required', 'example' => 'Main Branch'],
                    'supplier' => ['field' => 'supplier_id', 'lookup' => 'supplier', 'rules' => 'nullable', 'example' => ''],
                    'description' => ['rules' => 'nullable|string', 'example' => ''],
                ],
            ],
            'delivery_zones' => [
                'label' => 'Delivery Zones',
                'model' => DeliveryZone::class,
                'permission' => 'manage_settings',
                'index' => 'delivery_zones.index',
                'match' => ['zone_name'],
                'columns' => [
                    'zone_name' => ['rules' => 'required|string|max:255', 'example' => 'Kampala Central'],
                    'fee' => ['rules' => 'required|numeric|min:0', 'example' => '5000'],
                    'description' => ['rules' => 'nullable|string', 'example' => ''],
                    'is_active' => ['rules' => 'boolean', 'type' => 'boolean', 'default' => '1', 'example' => 'yes'],
                ],
            ],
            'chart_of_accounts' => [
                'label' => 'Chart of Accounts',
                'model' => ChartOfAccount::class,
                'permission' => 'manage_statements',
                'index' => 'chart_of_accounts.index',
                'match' => ['account_code'],
                'columns' => [
                    'account_code' => ['rules' => 'required|string|max:50', 'example' => '6100'],
                    'account_name' => ['rules' => 'required|string|max:255', 'example' => 'Transport Expense'],
                    'account_type' => ['rules' => 'required|in:Asset,Liability,Equity,Revenue,COGS,Expense', 'example' => 'Expense'],
                    'account_category' => ['rules' => 'required|string|max:255', 'example' => 'Operating Expenses'],
                    'normal_balance' => ['rules' => 'required|in:Debit,Credit', 'example' => 'Debit'],
                    'parent_code' => ['rules' => 'nullable|string|max:50', 'example' => '6000'],
                    'is_active' => ['rules' => 'boolean', 'type' => 'boolean', 'default' => '1', 'example' => 'yes'],
                    'description' => ['rules' => 'nullable|string', 'example' => ''],
                ],
            ],
            'product_branches' => [
                'label' => 'Product Branches',
                'model' => ProductBranch::class,
                'permission' => 'manage_products',
                'index' => 'product_branches.index',
                'handler' => 'product_branches',
                'note' => 'Makes each product available in the listed branches. Existing links are kept.',
                'columns' => [
                    'product' => ['field' => 'product_id', 'lookup' => 'product', 'rules' => 'required', 'example' => 'Example Product', 'help' => 'Product name or SKU'],
                    'branches' => ['field' => 'branch_ids', 'lookup' => 'branches', 'rules' => 'required', 'example' => 'All Branches', 'help' => 'One branch, several separated by ";" or "All Branches"'],
                ],
            ],
        ];
    }

    /** Pages whose import is the dedicated products/customers importer on the Bulk Upload page. */
    public const BULK_UPLOAD_PAGE = [
        'products' => 'manage_products',
        'customers' => 'manage_customers',
    ];

    public static function get(string $key): ?array
    {
        $module = self::all()[$key] ?? null;

        return $module ? $module + ['key' => $key] : null;
    }
}
