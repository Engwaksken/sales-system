<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
        // Uses updateOrInsert on 'account_code' so the seeder is safe to re-run.
    public function run(): void
    {
        $rows = [
            ['account_code' => '1000', 'account_name' => 'Cash - Main Branch', 'account_type' => 'Asset', 'account_category' => 'Current Asset', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Physical cash held at Main Branch', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '1001', 'account_name' => 'Cash - Branch 2', 'account_type' => 'Asset', 'account_category' => 'Current Asset', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Physical cash held at Branch 2', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '1010', 'account_name' => 'Mobile Money Receipts', 'account_type' => 'Asset', 'account_category' => 'Current Asset', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'MTN/Airtel Mobile Money collections', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '1100', 'account_name' => 'Accounts Receivable', 'account_type' => 'Asset', 'account_category' => 'Current Asset', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Amounts owed by customers (credit sales)', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '1200', 'account_name' => 'Inventory - Main Branch', 'account_type' => 'Asset', 'account_category' => 'Current Asset', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Stock at Main Branch valued at cost', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '1201', 'account_name' => 'Inventory - Branch 2', 'account_type' => 'Asset', 'account_category' => 'Current Asset', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Stock at Branch 2 valued at cost', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '1900', 'account_name' => 'Other Current Assets', 'account_type' => 'Asset', 'account_category' => 'Current Asset', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Prepayments and sundry current assets', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '2000', 'account_name' => 'Accounts Payable', 'account_type' => 'Liability', 'account_category' => 'Current Liability', 'normal_balance' => 'Credit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Amounts owed to suppliers', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '2100', 'account_name' => 'VAT Payable', 'account_type' => 'Liability', 'account_category' => 'Current Liability', 'normal_balance' => 'Credit', 'is_active' => '1', 'parent_code' => null, 'description' => '18% VAT collected on sales, remittable to URA', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '2200', 'account_name' => 'Accrued Expenses', 'account_type' => 'Liability', 'account_category' => 'Current Liability', 'normal_balance' => 'Credit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Expenses incurred but not yet paid', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '3000', 'account_name' => 'Owner\'s Capital', 'account_type' => 'Equity', 'account_category' => 'Equity', 'normal_balance' => 'Credit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Initial and additional capital injected by owner', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '3100', 'account_name' => 'Retained Earnings', 'account_type' => 'Equity', 'account_category' => 'Equity', 'normal_balance' => 'Credit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Cumulative net income/(loss) carried forward', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '4000', 'account_name' => 'Sales Revenue', 'account_type' => 'Revenue', 'account_category' => 'Operating Revenue', 'normal_balance' => 'Credit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Revenue from goods sold (net of VAT)', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '4100', 'account_name' => 'Other Income', 'account_type' => 'Revenue', 'account_category' => 'Other Income', 'normal_balance' => 'Credit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Miscellaneous income', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '4900', 'account_name' => 'Discount Allowed', 'account_type' => 'Revenue', 'account_category' => 'Contra Revenue', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Trade discounts granted to customers (contra-revenue)', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '5000', 'account_name' => 'Cost of Goods Sold', 'account_type' => 'COGS', 'account_category' => 'Cost of Sales', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Direct cost of inventory sold', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '6000', 'account_name' => 'Salaries & Wages', 'account_type' => 'Expense', 'account_category' => 'Operating Expense', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Staff payroll costs', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '6100', 'account_name' => 'Rent Expense', 'account_type' => 'Expense', 'account_category' => 'Operating Expense', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Office and premises rental', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '6200', 'account_name' => 'Utilities Expense', 'account_type' => 'Expense', 'account_category' => 'Operating Expense', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Electricity, water and internet', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '6300', 'account_name' => 'Transport & Logistics', 'account_type' => 'Expense', 'account_category' => 'Operating Expense', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Transport, fuel, delivery costs', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '6400', 'account_name' => 'Marketing & Advertising', 'account_type' => 'Expense', 'account_category' => 'Operating Expense', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Digital and physical advertising', 'created_at' => '2026-03-08 05:50:50'],
            ['account_code' => '6500', 'account_name' => 'Office Supplies', 'account_type' => 'Expense', 'account_category' => 'Operating Expense', 'normal_balance' => 'Debit', 'is_active' => '1', 'parent_code' => null, 'description' => 'Stationery and consumables', 'created_at' => '2026-03-08 05:50:50'],
        ];

        foreach ($rows as $row) {
            \App\Models\ChartOfAccount::updateOrCreate(['account_code' => $row['account_code']], $row);
        }
    }
}
