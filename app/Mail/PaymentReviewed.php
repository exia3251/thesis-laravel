<?php

namespace App\Mail;

use App\Models\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a customer what became of the GCash payment they submitted.
 *
 * One mailable for both outcomes rather than two, because the question it
 * answers is the same either way -- a customer who uploaded a receipt and
 * heard nothing is waiting on this regardless of which way it went.
 */
class PaymentReviewed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PaymentRequest $paymentRequest)
    {
    }

    public function envelope(): Envelope
    {
        $approved = $this->paymentRequest->status === 'approved';
        $reference = $this->paymentRequest->sale->reference();

        return new Envelope(
            subject: ($approved ? 'Payment received for order ' : 'We could not confirm your payment for order ')
                . $reference . ' - ' . config('business.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-reviewed',
            with: [
                'paymentRequest' => $this->paymentRequest,
                'sale' => $this->paymentRequest->sale,
                'approved' => $this->paymentRequest->status === 'approved',
                'url' => url('/orders/' . $this->paymentRequest->sale->sale_id),
            ],
        );
    }
}
