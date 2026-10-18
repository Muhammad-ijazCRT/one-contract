<?php

namespace App\Console\Commands;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendVerificationTestCommand extends Command
{
    protected $signature = 'mail:test-verification {email : Recipient email address}';

    protected $description = 'Send a test signup verification email';

    public function handle(EmailVerificationService $verification): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        $this->info('Mailer: '.config('mail.default'));
        $this->info('From: '.config('mail.from.address'));
        $this->info('SMTP host: '.config('mail.mailers.smtp.host'));
        $this->info('SMTP EHLO: '.(string) config('mail.mailers.smtp.local_domain'));
        $publicBase = \App\Support\MailPublicUrl::base();
        $this->info('Public mail URL: '.($publicBase ?: '(missing — set MAIL_PUBLIC_URL=https://one-contract.net)'));

        try {
            if ($user) {
                $verification->send($user);
            } else {
                $this->warn("No user found for {$email}. Sending template preview.");

                Mail::to($email)->send(new VerifyEmailMail(
                    name: 'ONE CONTRACT User',
                    url: (\App\Support\MailPublicUrl::page('email/verify') ?: 'https://one-contract.net/email/verify').'?email='.rawurlencode($email),
                ));
            }

            $this->info('Verification email sent to '.$email);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
