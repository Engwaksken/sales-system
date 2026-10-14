<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slides', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('');
            $table->text('subtitle')->nullable();
            $table->string('badge_text', 100)->default('Featured');
            $table->string('image_url', 500)->default('');
            $table->string('cta_label', 100)->default('Shop Now');
            $table->string('cta_link', 500)->default('#');
            $table->string('bg_color', 50)->default('');
            $table->smallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slides');
    }
};
