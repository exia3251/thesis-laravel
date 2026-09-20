<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stops recording the source address against audited actions.
 *
 * On a single-machine install every entry reads 127.0.0.1, so the column
 * carried no information while still being personal data the system had to
 * justify holding. The audit trail keeps what actually identifies an action:
 * who did it, what they did, and when.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('description');
        });
    }
};
