<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedCustomerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order
    ) {}

    public function envelope(): Envelope
    {
        $businessName = (string) config('business.name', 'JC Autopartes');

        return new Envelope(
            subject: "¡Pago Confirmado! Tu orden {$this->order->order_number} en {$businessName}",
            replyTo: [
                new Address((string) config('business.email', 'jcautopartes.mx@gmail.com'), $businessName),
            ]
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.customer_confirmed',
            with: [
                'order' => $this->order,
                'business' => config('business'),
            ]
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
