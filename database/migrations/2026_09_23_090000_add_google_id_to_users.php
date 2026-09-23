<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links an account to a Google identity.
 *
 * Sign-in matches on this rather than on the email address. Google's subject
 * id is stable and belongs to one account for ever, whereas an email address
 * can be changed here or reassigned there, and trusting it would mean anyone
 * who came to control a mailbox could sign in as whoever used it before.
 *
 * Unique, so one Google account cannot end up attached to two of ours.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 40)->nullable()->unique()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
        });
    }
};
