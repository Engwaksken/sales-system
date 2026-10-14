<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'user_id')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        // Link existing customers to same-email customer users.
        DB::table('customers')
            ->whereNull('user_id')
            ->orderBy('id')
            ->eachById(function ($customer): void {
                if (! $customer->email) {
                    return;
                }

                $userId = DB::table('users')
                    ->where('email', $customer->email)
                    ->value('id');

                if ($userId) {
                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update(['user_id' => $userId]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'user_id')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
