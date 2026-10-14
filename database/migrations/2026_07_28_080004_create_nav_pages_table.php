<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nav_pages', function (Blueprint $table) {
            $table->id();
            $table->string('label', 80);
            $table->string('url', 200);
            $table->string('icon', 80)->default('fa-file');
            $table->foreignId('parent_id')->nullable()->constrained('nav_pages')->nullOnDelete();
            $table->smallInteger('sort_order')->default(0);
            $table->string('permission_key', 80);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nav_pages');
    }
};
