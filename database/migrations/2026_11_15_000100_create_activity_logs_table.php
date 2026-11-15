<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // Kept as plain columns (no foreign keys) so the trail survives deleted users and records.
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('user_role', 50)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('action', 30)->index();
            $table->string('subject_type', 50)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 500);
            $table->json('changes')->nullable();
            $table->string('context', 50)->nullable();
            $table->boolean('flagged')->default(false)->index();
            $table->string('flag_reason', 255)->nullable();
            $table->string('source', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['subject_type', 'subject_id']);
        });

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->updateOrInsert(
                ['permission_key' => 'view_activity_logs'],
                [
                    'label' => 'View Activity Logs',
                    'category' => 'Admin',
                    'description' => 'See who created, changed or deleted sales, stock, payments and other records',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('permission_key', 'view_activity_logs')->delete();
        }
    }
};
