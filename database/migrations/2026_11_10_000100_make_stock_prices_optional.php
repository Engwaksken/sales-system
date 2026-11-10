<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cost and selling price may be left blank when stock is recorded and filled in
 * later. NULL means "not set" (as opposed to a real price of 0); stock without a
 * selling price can't be sold at the till or online until one is entered.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->nullable()->default(null)->change();
            $table->decimal('selling_price', 10, 2)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        DB::table('stock')->whereNull('cost_price')->update(['cost_price' => 0]);
        DB::table('stock')->whereNull('selling_price')->update(['selling_price' => 0]);

        Schema::table('stock', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->default(0)->change();
            $table->decimal('selling_price', 10, 2)->default(0)->change();
        });
    }
};
