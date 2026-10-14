<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            BranchSeeder::class,
            AdminUserSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            ChatPermissionSeeder::class,
            ChartOfAccountSeeder::class,
            DeliveryZoneSeeder::class,
            ExpenseCategorySeeder::class,
            NavPageSeeder::class,
            PaymentGatewaySeeder::class,
        ]);
    }
}
