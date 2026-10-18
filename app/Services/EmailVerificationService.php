<?php

namespace App\Services;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class EmailVerificationService
{
    public function send(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid email address is required to send verification mail.');
        }

        $this->assertMailConfigured();

        Mail::to($user->email)->send(new VerifyEmailMail(
            name: $user->name ?: 'User',
            url: $this->verificationUrl($user),
        ));
    }

    public function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );
    }

    private function assertMailConfigured(): void
    {
        $mailer = (string) config('mail.default', 'log');
        $from = (string) config('mail.from.address', '');

        if ($from === '' || $from === 'hello@example.com') {
            throw new RuntimeException('MAIL_FROM_ADDRESS is not configured.');
        }

        if ($mailer === 'log') {
            Log::warning('Verification email will be written to logs because MAIL_MAILER=log.');
        }

        if ($mailer === 'smtp') {
            $host = (string) config('mail.mailers.smtp.host', '');
            $username = (string) config('mail.mailers.smtp.username', '');

            if ($host === '' || $host === '127.0.0.1') {
                throw new RuntimeException('SMTP host is not configured.');
            }

            if ($username === '') {
                throw new RuntimeException('SMTP username is not configured.');
            }
        }
    }
}
