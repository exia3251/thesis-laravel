<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An account created through Google Sign-In has never been asked for a phone
 * number, so its profile begins without one. The column required a value,
 * which meant such a profile could not be created at all, and the customer
 * reached their own account page only to be told it did not exist.
 *
 * Checkout still refuses to proceed without a phone; that rule lives in
 * CustomerProfile::isComplete(), where it can say so in a sentence rather
 * than as a constraint violation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->string('phone', 20)->nullable(false)->default('')->change();
        });
    }
};
