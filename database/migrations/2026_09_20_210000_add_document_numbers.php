<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Human-facing document numbers for receipts and delivery notes.
 *
 * The running count lives in its own table rather than being derived from
 * MAX(receipt_no) at issue time: two orders settling at the same moment would
 * both read the same maximum and try to claim the same number. A row per
 * series and year can be locked for the moment it takes to increment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('series', 8);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['series', 'year']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('receipt_no', 24)->nullable()->unique()->after('sale_id');
            $table->string('delivery_no', 24)->nullable()->unique()->after('receipt_no');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['receipt_no']);
            $table->dropUnique(['delivery_no']);
            $table->dropColumn(['receipt_no', 'delivery_no']);
        });

        Schema::dropIfExists('document_sequences');
    }
};
