<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\DocumentNumber;

/**
 * Issues receipt and delivery numbers the moment an order reaches the state
 * that earns one.
 *
 * This lives in an observer rather than in the controllers because an order
 * can be settled from several directions - a payment request approved, a
 * cash amount recorded by staff, a customer confirming receipt - and every
 * one of them has to produce a number. Putting the rule next to the state it
 * watches means a new path cannot forget to call it.
 */
class SaleObserver
{
    public function saved(Sale $sale): void
    {
        $updates = [];

        // An order reference is earned by existing, not by being paid, so it
        // is issued ahead of the cancelled guard below. A cancelled order
        // still has to be referred to afterwards.
        if (blank($sale->order_no)) {
            $updates['order_no'] = DocumentNumber::next(DocumentNumber::ORDER);
        }

        // A cancelled order earns no further document. One issued before it
        // was called off stays, because that receipt really was given out.
        if ($sale->isCancelled()) {
            if ($updates) {
                Sale::query()->where('sale_id', $sale->sale_id)->update($updates);
                $sale->forceFill($updates);
            }

            return;
        }

        if (blank($sale->receipt_no) && $sale->payment_status === 'paid') {
            $updates['receipt_no'] = DocumentNumber::next(DocumentNumber::RECEIPT);
        }

        if (blank($sale->delivery_no) && $sale->delivery_status === 'delivered') {
            $updates['delivery_no'] = DocumentNumber::next(DocumentNumber::DELIVERY);
        }

        if (!$updates) {
            return;
        }

        // Written through the query builder so this observer is not re-entered.
        Sale::query()->where('sale_id', $sale->sale_id)->update($updates);

        $sale->forceFill($updates);
    }
}
