<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iotec_collections', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 50);
            $table->string('phone', 20);
            $table->string('network', 10)->default('');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('iotec_ref', 120)->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('raw_response')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iotec_collections');
    }
};
