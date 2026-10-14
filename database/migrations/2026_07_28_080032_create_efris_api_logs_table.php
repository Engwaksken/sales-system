<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('efris_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->constrained('efris_config')->cascadeOnDelete();
            $table->string('operation_code', 20);
            $table->string('operation_name', 150);
            $table->string('http_method', 10)->default('POST');
            $table->string('endpoint_url');
            $table->string('reference_type', 30)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->longText('request_body')->nullable();
            $table->longText('response_body')->nullable();
            $table->integer('http_status_code')->nullable();
            $table->string('efris_return_code', 20)->nullable();
            $table->string('efris_return_msg', 500)->nullable();
            $table->boolean('is_success')->default(false);
            $table->integer('latency_ms')->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('efris_api_logs');
    }
};
