<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['damage', 'gratis', 'staff_consumption']);
            $table->unsignedInteger('quantity');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_issues');
    }
};
