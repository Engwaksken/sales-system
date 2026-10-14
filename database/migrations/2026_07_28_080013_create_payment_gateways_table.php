<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name', 100);
            $table->string('gateway_code', 50)->unique();
            $table->string('display_name', 150);
            $table->enum('provider_type', ['mobile_money','card','bank','wallet','mixed'])->default('mobile_money');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('sandbox_mode')->default(true);
            $table->string('token_url')->nullable();
            $table->string('collect_url')->nullable();
            $table->string('base_url')->nullable();
            $table->string('status_url')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_secret')->nullable();
            $table->string('wallet_guid')->nullable();
            $table->string('callback_url')->nullable();
            $table->string('return_url')->nullable();
            $table->boolean('supports_collection')->default(true);
            $table->boolean('supports_disbursement')->default(false);
            $table->boolean('supports_mtn')->default(true);
            $table->boolean('supports_airtel')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
