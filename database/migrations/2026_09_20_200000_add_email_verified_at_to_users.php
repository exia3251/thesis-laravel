<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Email verification is not decoration here: it is what makes linking a
 * Google sign-in to an existing password account safe. Without it, anyone
 * could register under someone else's address and wait to be handed their
 * account when they later sign in with Google.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });

        // Accounts that predate verification are trusted, so existing
        // customers are not locked out of ordering by this change.
        DB::table('users')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
