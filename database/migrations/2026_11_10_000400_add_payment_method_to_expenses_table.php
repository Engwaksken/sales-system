<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** How an expense was paid (Cash, Mobile Money, ...), so expenses can be filtered by it. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('expenses', 'payment_method')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('payment_method', 100)->nullable()->after('amount')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('expenses', 'payment_method')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropIndex(['payment_method']);
                $table->dropColumn('payment_method');
            });
        }
    }
};
