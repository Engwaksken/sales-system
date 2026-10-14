<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
        // Uses updateOrInsert on 'name' so the seeder is safe to re-run.
    public function run(): void
    {
        $rows = [
            ['name' => 'Transportation', 'status' => '1', 'created_at' => '2026-03-08 05:50:50'],
            ['name' => 'Rent', 'status' => '1', 'created_at' => '2026-03-08 05:50:50'],
            ['name' => 'Utilities', 'status' => '1', 'created_at' => '2026-03-08 05:50:50'],
            ['name' => 'Salaries', 'status' => '1', 'created_at' => '2026-03-08 05:50:50'],
            ['name' => 'Supplies', 'status' => '1', 'created_at' => '2026-03-08 05:50:50'],
            ['name' => 'Marketing', 'status' => '1', 'created_at' => '2026-03-08 05:50:50'],
        ];

        foreach ($rows as $row) {
            \App\Models\ExpenseCategory::updateOrCreate(['name' => $row['name']], $row);
        }
    }
}
