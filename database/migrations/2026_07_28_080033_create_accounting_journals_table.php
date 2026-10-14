<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->id();
            $table->string('journal_no', 20);
            $table->enum('journal_type', ['GJ','SJ','PJ','CRJ','CPJ','MJ'])->default('GJ');
            $table->string('reference_no', 50)->nullable();
            $table->string('reference_type', 30)->nullable();
            $table->integer('reference_id')->nullable();
            $table->date('journal_date');
            $table->string('description', 500);
            $table->foreignId('branch_id')->nullable()->default(1)->constrained('branches')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->default(1)->constrained('users')->nullOnDelete();
            $table->boolean('is_posted')->default(true);
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journals');
    }
};
