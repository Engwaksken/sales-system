<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('order_number', 100);
            $table->string('receipt_number', 100);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('payment_method', 100);
            $table->string('gateway_code', 100)->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_status', 100)->nullable();
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->string('currency', 10)->default('UGX');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_payment_receipts');
    }
};
