<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DeliveryZoneSeeder extends Seeder
{
        // Uses updateOrInsert on 'zone_name' so the seeder is safe to re-run.
    public function run(): void
    {
        $rows = [
            ['zone_name' => 'Kampala Central', 'fee' => '3000.00', 'description' => 'CBD & Nakasero', 'is_active' => '1', 'created_at' => '2026-04-06 14:31:45'],
            ['zone_name' => 'Nakawa / Ntinda', 'fee' => '4000.00', 'description' => 'Nakawa division', 'is_active' => '1', 'created_at' => '2026-04-06 14:31:45'],
            ['zone_name' => 'Makindye / Nsambya', 'fee' => '4000.00', 'description' => 'Makindye division', 'is_active' => '1', 'created_at' => '2026-04-06 14:31:45'],
            ['zone_name' => 'Rubaga / Namirembe', 'fee' => '4000.00', 'description' => 'Rubaga division', 'is_active' => '1', 'created_at' => '2026-04-06 14:31:45'],
            ['zone_name' => 'Kawempe / Bwaise', 'fee' => '4500.00', 'description' => 'Kawempe division', 'is_active' => '1', 'created_at' => '2026-04-06 14:31:45'],
            ['zone_name' => 'Entebbe', 'fee' => '8000.00', 'description' => 'Entebbe town & airport area', 'is_active' => '1', 'created_at' => '2026-04-06 14:31:45'],
            ['zone_name' => 'Wakiso / Nansana', 'fee' => '5500.00', 'description' => 'Wakiso town & environs', 'is_active' => '1', 'created_at' => '2026-04-06 14:31:45'],
            ['zone_name' => 'Mukono', 'fee' => '8000.00', 'description' => 'Mukono town & outskirts', 'is_active' => '1', 'created_at' => '2026-04-06 14:31:45'],
            ['zone_name' => 'Outside Kampala Metro', 'fee' => '12000.00', 'description' => 'Jinja, Masaka, Mbarara etc.', 'is_active' => '1', 'created_at' => '2026-04-06 14:31:45'],
        ];

        foreach ($rows as $row) {
            \App\Models\DeliveryZone::updateOrCreate(['zone_name' => $row['zone_name']], $row);
        }
    }
}
