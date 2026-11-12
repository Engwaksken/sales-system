<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->timestamps();
        });
        foreach ([
            'admin' => 'Admin', 'general_manager' => 'General Manager', 'manager' => 'Manager',
            'marketing_manager' => 'Marketing Manager', 'accountant' => 'Accountant',
            'cashier' => 'Cashier', 'customer' => 'Customer',
        ] as $name => $label) {
            DB::table('roles')->insert(['name' => $name, 'label' => $label, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
    public function down(): void { Schema::dropIfExists('roles'); }
};
