<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
        // Uses updateOrInsert on 'name' so the seeder is safe to re-run.
    public function run(): void
    {
        $rows = [
            ['name' => 'Main Branch', 'location' => 'Kampala, Uganda', 'contact' => '0784675791', 'branch_email' => 'wakskenneth@gmail.com', 'created_at' => '2025-12-23 17:32:03'],
            ['name' => 'KENNN', 'location' => 'Kampala, Uganda', 'contact' => '0784675791', 'branch_email' => 'wakskenneth2@gmail.com', 'created_at' => '2026-01-10 14:29:52'],
        ];

        foreach ($rows as $row) {
            \App\Models\Branch::updateOrCreate(['name' => $row['name']], $row);
        }
    }
}
