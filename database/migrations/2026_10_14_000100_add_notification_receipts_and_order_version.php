<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('notification_version')->default(1);
        });

        Schema::create('system_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('notification_id')->index()->constrained('notifications_app')->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            // The unique index also covers lookups by user_id.
            $table->unique(['user_id', 'notification_id'], 'system_notification_reads_user_notification_unique');
        });

        Schema::create('order_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->index()->constrained('orders')->cascadeOnDelete();
            $table->unsignedBigInteger('notification_version');
            $table->timestamp('read_at');
            $table->timestamps();

            // The unique index also covers lookups by user_id and user_id/order_id.
            $table->unique(['user_id', 'order_id', 'notification_version'], 'order_notification_reads_user_order_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notification_reads');
        Schema::dropIfExists('system_notification_reads');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('notification_version');
        });
    }
};
