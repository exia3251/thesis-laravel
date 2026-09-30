<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The parts of the storefront the business can change for itself.
 *
 * The telephone number, the address, the email, the opening hours and the
 * GCash QR code were fixed in configuration, which meant a new number needed
 * somebody to edit a file on the server and restart. That is fine for the
 * people who built this and no use at all to the people who run it.
 *
 * Held as keys and values rather than as columns, because the set of things
 * worth editing grows: adding one is a new key, not a migration. Nothing here
 * is required -- a key that has never been set falls back to the value in
 * config/business.php, so the system runs exactly as before until somebody
 * edits something.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('key', 64)->primary();

            // Text rather than string: the tagline is a sentence, and an
            // image key holds a path.
            $table->text('value')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
