<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('efris_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('tin', 50);
            $table->string('taxpayer_name');
            $table->text('taxpayer_address')->nullable();
            $table->string('taxpayer_email', 150)->nullable();
            $table->string('taxpayer_phone', 50)->nullable();
            $table->string('business_type', 50)->nullable();
            $table->boolean('vat_registered')->default(false);
            $table->enum('environment', ['sandbox','production'])->default('sandbox');
            $table->text('private_key_pem')->nullable();
            $table->text('public_key_pem')->nullable();
            $table->text('ura_public_key_pem')->nullable();
            $table->string('portal_username', 100)->nullable();
            $table->text('portal_password_enc')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efris_config');
    }
};
