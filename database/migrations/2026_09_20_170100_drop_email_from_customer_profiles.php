<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * users.email is now the login identifier, so the copy on customer_profiles
 * is redundant. Leaving both would let a profile edit and a login credential
 * drift apart. The values were already copied across by the previous
 * migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Uniqueness was only ever enforced by the validation rules, never by
        // an index, so there is nothing to drop but the column itself.
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->string('email', 100)->nullable()->after('phone');
        });

        DB::statement('
            UPDATE customer_profiles p
            JOIN users u ON u.user_id = p.user_id
            SET p.email = u.email
        ');
    }
};
