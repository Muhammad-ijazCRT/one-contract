<?php

namespace App\Mail;

use App\Support\MailPublicUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerifyEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $url,
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');

        return new Envelope(
            subject: 'Confirm your One Contract email',
            replyTo: $fromAddress !== '' ? [new Address($fromAddress, $fromName)] : [],
        );
    }

    public function headers(): Headers
    {
        $from = (string) config('mail.from.address');
        $unsubPage = MailPublicUrl::page('contact');
        $listUnsub = $from !== '' ? '<mailto:'.$from.'?subject=unsubscribe>' : '';

        if ($unsubPage) {
            $listUnsub = '<'.$unsubPage.'>'.($listUnsub !== '' ? ', '.$listUnsub : '');
        }

        $text = [
            'Auto-Submitted' => 'auto-generated',
            'X-Auto-Response-Suppress' => 'All',
            'X-Mailer' => 'One Contract',
        ];

        if ($listUnsub !== '') {
            $text['List-Unsubscribe'] = $listUnsub;
        }

        return new Headers(
            messageId: bin2hex(random_bytes(16)).'@'.MailPublicUrl::fromDomain(),
            text: $text,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.verify-email',
            text: 'emails.verify-email-text',
        );
    }
}
