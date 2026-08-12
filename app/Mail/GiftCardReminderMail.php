<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\GiftCard;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GiftCardReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public GiftCard $giftCard,
        public int $daysUntil,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Infinecs'),
            subject: 'Action needed: prepare ' . $this->employee->name . "'s birthday gift card",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gift-card-reminder',
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
