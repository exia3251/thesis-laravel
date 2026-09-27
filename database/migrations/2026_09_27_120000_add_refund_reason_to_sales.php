<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Why a refund was asked for.
 *
 * A refund used to arrive only through a cancellation, where the reason lived
 * in cancellation_reason. A customer can now ask for one on an order that has
 * already been delivered -- there is nothing left to cancel by then -- and
 * that reason is a different thing: the order stands, the goods arrived, and
 * something about them was wrong. Recording it under "cancellation" would
 * have made the receipt say the order was cancelled when it was not.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->text('refund_reason')->nullable()->after('refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('refund_reason');
        });
    }
};
