<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Path (on the private "local" disk) of the user's signature image, printed on invoices and receipts. */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'signature')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('signature')->nullable()->after('profile_pic');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'signature')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('signature');
            });
        }
    }
};
