<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Roles are managed in the roles table, so users.role must not be a
        // fixed ENUM that rejects valid roles added after deployment.
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('cashier')->change();
        });
    }

    public function down(): void
    {
        // Intentionally keep the flexible string column. Reverting to an ENUM
        // could truncate users assigned a dynamically-created role.
    }
};
