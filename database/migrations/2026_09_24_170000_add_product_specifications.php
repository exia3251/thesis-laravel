<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the manufacturer says about the oil, beyond a sentence.
 *
 * `description` stays the short paragraph that a card or an email can quote.
 * Everything a buyer actually checks before committing to a drum -- what it
 * is approved against, where it is meant to be used, and the physical
 * properties Solar publish -- goes here as JSON, because it is a different
 * shape for each manufacturer and there is no table that fits both.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('specifications')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('specifications');
        });
    }
};
