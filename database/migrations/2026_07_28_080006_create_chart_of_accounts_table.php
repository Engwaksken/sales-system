<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 10)->unique();
            $table->string('account_name', 150);
            $table->enum('account_type', ['Asset','Liability','Equity','Revenue','COGS','Expense']);
            $table->string('account_category', 100);
            $table->enum('normal_balance', ['Debit','Credit']);
            $table->boolean('is_active')->default(true);
            $table->string('parent_code', 10)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
