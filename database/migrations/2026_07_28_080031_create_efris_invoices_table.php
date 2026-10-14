<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('efris_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('config_id')->nullable()->constrained('efris_config')->nullOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('efris_devices')->nullOnDelete();
            $table->string('invoice_no', 100);
            $table->string('tin', 50);
            $table->dateTime('issued_at');
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('submitted');
            $table->string('qr_code')->nullable();
            $table->string('verification_code', 100)->nullable();
            $table->longText('request_json')->nullable();
            $table->longText('response_json')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efris_invoices');
    }
};
