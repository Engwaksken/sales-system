<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->nullable();
            $table->string('email', 150)->unique()->nullable();
            $table->string('password')->nullable();
            $table->enum('role', ['admin','cashier','customer','manager'])->default('cashier');
            $table->foreignId('branch_id')->nullable()->default(1)->constrained('branches')->nullOnDelete();
            $table->string('profile_pic')->nullable();
            $table->string('verification_code', 6)->nullable();
            $table->dateTime('code_expires')->nullable();
            $table->rememberToken();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
