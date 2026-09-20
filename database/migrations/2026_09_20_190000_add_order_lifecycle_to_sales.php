<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives an order somewhere to record being called off, refunded, delivered
 * and received.
 *
 * Cancellation is kept separate from payment_status and delivery_status
 * rather than folded into either: a cancelled order still needs to remember
 * what was paid, so the money can be sent back.
 *
 * The audit columns use nullOnDelete rather than the restricting default,
 * so they never become another reason an account cannot be removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('order_status', ['active', 'cancelled'])
                ->default('active')
                ->after('delivery_status');

            $table->timestamp('cancelled_at')->nullable()->after('order_status');
            $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancellation_reason');

            // Money already collected has to be returned by hand through GCash,
            // so the system records the obligation and who settled it.
            $table->enum('refund_status', ['none', 'pending', 'refunded'])
                ->default('none')
                ->after('cancelled_by');
            $table->decimal('refund_amount', 10, 2)->default(0)->after('refund_status');
            $table->string('refund_reference', 100)->nullable()->after('refund_amount');
            $table->text('refund_notes')->nullable()->after('refund_reference');
            $table->timestamp('refunded_at')->nullable()->after('refund_notes');
            $table->unsignedBigInteger('refunded_by')->nullable()->after('refunded_at');

            // Proof that the goods actually arrived, photographed on handover.
            $table->string('delivery_proof_path')->nullable()->after('refunded_by');
            $table->timestamp('delivered_at')->nullable()->after('delivery_proof_path');
            $table->unsignedBigInteger('delivery_confirmed_by')->nullable()->after('delivered_at');

            // The customer's own acknowledgement, separate from the staff record.
            $table->timestamp('received_at')->nullable()->after('delivery_confirmed_by');

            $table->foreign('cancelled_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('refunded_by')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('delivery_confirmed_by')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['refunded_by']);
            $table->dropForeign(['delivery_confirmed_by']);

            $table->dropColumn([
                'order_status',
                'cancelled_at',
                'cancellation_reason',
                'cancelled_by',
                'refund_status',
                'refund_amount',
                'refund_reference',
                'refund_notes',
                'refunded_at',
                'refunded_by',
                'delivery_proof_path',
                'delivered_at',
                'delivery_confirmed_by',
                'received_at',
            ]);
        });
    }
};
