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

class BirthdayGiftCardMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Employee $employee,
        public GiftCard $giftCard,
        public bool $isTest = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = "🎉 Happy Birthday, {$this->employee->name}! Here's your gift";

        return new Envelope(
            from: new Address(config('mail.from.address'), 'Infinecs'),
            subject: $this->isTest ? '[TEST] ' . $subject : $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.birthday-gift-card',
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
