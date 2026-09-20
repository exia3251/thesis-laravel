<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Calling off an order touches stock, money owed and the audit trail at once,
 * and both the customer and the back office can trigger it, so the work lives
 * in one place rather than being written twice.
 */
class OrderCancellation
{
    /**
     * @throws RuntimeException when the order is past the point of cancelling.
     */
    public function cancel(Sale $sale, User $actor, ?string $reason): Sale
    {
        if ($sale->isCancelled()) {
            throw new RuntimeException('This order has already been cancelled.');
        }

        if ($sale->isDelivered()) {
            throw new RuntimeException('A delivered order cannot be cancelled.');
        }

        return DB::transaction(function () use ($sale, $actor, $reason) {
            // Put the goods back before anything else, so a failure here leaves
            // the order untouched rather than cancelled with stock still held.
            foreach ($sale->items as $item) {
                Inventory::updateStock($item->product_id, $item->quantity);

                StockTransaction::logTransaction(
                    $item->product_id,
                    'stock_in',
                    $item->quantity,
                    'CANCEL-' . $sale->sale_id,
                    'Returned to stock by cancellation',
                    $actor->user_id
                );
            }

            $paid = (float) $sale->paid_amount;

            $sale->order_status = Sale::STATUS_CANCELLED;
            $sale->cancelled_at = now();
            $sale->cancellation_reason = $reason;
            $sale->cancelled_by = $actor->user_id;

            // Money already collected has to be returned by hand; the order
            // carries the obligation until somebody records having sent it.
            if ($paid > 0) {
                $sale->refund_status = Sale::REFUND_PENDING;
                $sale->refund_amount = $paid;
            }

            $sale->balance_due = 0;
            $sale->save();

            $who = $actor->isCustomer() ? 'Customer' : $actor->roleLabel();

            ActivityLog::logAction(
                $actor->user_id,
                'order_cancelled',
                "{$who} {$actor->full_name} cancelled order #{$sale->sale_id}"
                    . ($paid > 0 ? " - PHP " . number_format($paid, 2) . ' refund owed' : '')
                    . ($reason ? ". Reason: {$reason}" : '')
            );

            return $sale;
        });
    }

    /**
     * Records that the refund owed on a cancelled order has actually been sent.
     * The transfer itself happens outside the system, in GCash.
     */
    public function markRefunded(Sale $sale, User $actor, ?string $reference, ?string $notes): Sale
    {
        if (!$sale->owesRefund()) {
            throw new RuntimeException('This order has no refund outstanding.');
        }

        $sale->refund_status = Sale::REFUND_DONE;
        $sale->refund_reference = $reference;
        $sale->refund_notes = $notes;
        $sale->refunded_at = now();
        $sale->refunded_by = $actor->user_id;
        $sale->save();

        ActivityLog::logAction(
            $actor->user_id,
            'refund_recorded',
            "{$actor->full_name} recorded a PHP " . number_format((float) $sale->refund_amount, 2)
                . " refund for order #{$sale->sale_id}"
                . ($reference ? " (ref {$reference})" : '')
        );

        return $sale;
    }
}
