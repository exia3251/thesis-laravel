<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records what the customer committed to at checkout, which is separate from
 * what they have actually paid so far.
 *
 * paid_amount and balance_due already track settlement. What was missing is
 * the intent: a split order needs to show "pay this much by GCash now, the
 * rest in cash on delivery" before any money has moved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('payment_plan', ['cod', 'gcash_full', 'split'])
                ->default('cod')
                ->after('payment_method');

            // The portion the customer committed to paying by GCash.
            // Zero for pure cash on delivery, the full total for gcash_full.
            $table->decimal('gcash_amount', 10, 2)->default(0)->after('payment_plan');
        });

        // Existing orders predate the plan choice, so infer it from the method
        // they were placed with.
        DB::statement("
            UPDATE sales
            SET payment_plan = 'gcash_full', gcash_amount = total_amount
            WHERE payment_method = 'gcash'
        ");

        DB::statement("
            UPDATE sales
            SET payment_plan = 'cod', gcash_amount = 0
            WHERE payment_method IS NULL OR payment_method <> 'gcash'
        ");
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['payment_plan', 'gcash_amount']);
        });
    }
};
