<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates one bootstrap admin account so you can log in immediately after migrating.
 *
 * IMPORTANT: change this password immediately after first login — it is a
 * placeholder, not a real credential. Never commit real credentials into a seeder.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kemmytech.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('ChangeMe123!'),
                'role' => 'admin',
                'branch_id' => 1,
            ]
        );
    }
}
