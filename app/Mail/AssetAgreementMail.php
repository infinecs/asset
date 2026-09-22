<?php

namespace App\Mail;

use App\Models\Asset;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssetAgreementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Asset $asset,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), 'Infinecs'),
            subject: "Action Required: Sign Your Equipment Agreement - {$this->asset->asset_tag}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.asset-agreement',
        );
    }
}
