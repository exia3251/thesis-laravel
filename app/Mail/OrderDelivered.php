<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderDelivered extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Sale $sale)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('business.document_title') . ' for order ' . $this->sale->reference() . ' - ' . config('business.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-delivered',
            with: [
                'sale' => $this->sale,
                'url' => url('/orders/' . $this->sale->sale_id),
            ],
        );
    }
}
