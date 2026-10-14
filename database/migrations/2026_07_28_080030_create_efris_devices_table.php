<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('efris_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->constrained('efris_config')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('device_serial_no', 100);
            $table->string('device_type', 50)->nullable();
            $table->string('device_name', 150)->nullable();
            $table->string('activation_code', 150)->nullable();
            $table->enum('activation_status', ['pending','activated','suspended'])->default('pending');
            $table->dateTime('activated_at')->nullable();
            $table->integer('invoice_counter')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efris_devices');
    }
};
