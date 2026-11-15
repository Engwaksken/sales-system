<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Common expense categories so the expense form has something to pick from on
 * every install. Existing categories (any spelling/case) are left untouched.
 */
return new class extends Migration
{
    public const DEFAULTS = [
        'Transport', 'Fuel', 'Rent', 'Electricity', 'Water', 'Salaries & Wages', 'Casual Labour',
        'Airtime & Data', 'Internet', 'Office Supplies', 'Stationery', 'Repairs & Maintenance',
        'Cleaning', 'Security', 'Meals & Refreshments', 'Staff Welfare', 'Marketing & Advertising',
        'Bank Charges', 'Mobile Money Charges', 'Taxes & Licences', 'Insurance', 'Loading & Offloading',
        'Packaging', 'Delivery', 'Professional Fees', 'Rates & Dues', 'Miscellaneous',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('expense_categories')) {
            return;
        }

        $existing = DB::table('expense_categories')->pluck('name')->map(fn ($n) => strtolower(trim((string) $n)))->all();
        $now = now();

        foreach (self::DEFAULTS as $name) {
            if (in_array(strtolower($name), $existing, true)) {
                continue;
            }
            DB::table('expense_categories')->insert([
                'name' => $name, 'status' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Categories may be in use by then; keep them.
    }
};
