<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock date now records when stock was received down to the minute (Kampala
 * time), defaulting to "now" on the New Stock form. Existing dates keep their day
 * at midnight.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->dateTime('stock_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock', function (Blueprint $table) {
            $table->date('stock_date')->nullable()->change();
        });
    }
};
