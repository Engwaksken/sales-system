<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock', function (Blueprint $table): void {
            if (! Schema::hasColumn('stock', 'stock_date')) {
                $table->date('stock_date')
                    ->nullable()
                    ->after('selling_price')
                    ->index();
            }
        });

        // Existing records continue to participate in monthly supplier analytics.
        if (Schema::hasColumn('stock', 'stock_date')) {
            DB::table('stock')
                ->whereNull('stock_date')
                ->update([
                    'stock_date' => DB::raw('DATE(created_at)'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('stock', function (Blueprint $table): void {
            if (Schema::hasColumn('stock', 'stock_date')) {
                $table->dropColumn('stock_date');
            }
        });
    }
};
