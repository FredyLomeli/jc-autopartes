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

class OrderNotificationAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚨 [Nueva Venta Web] Orden {$this->order->order_number} — Preparar refacción",
            replyTo: [
                new Address($this->order->customer_email, $this->order->customer_name),
            ]
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.admin_notification',
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
