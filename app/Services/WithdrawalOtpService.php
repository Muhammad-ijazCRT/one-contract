<?php

namespace App\Services;

use App\Mail\WithdrawalOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class WithdrawalOtpService
{
    private const TTL_SECONDS = 600;

    private const COOLDOWN_SECONDS = 60;

    public function send(User $user): void
    {
        if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email_otp' => 'A valid email address is required to send a withdrawal code.',
            ]);
        }

        $cooldownKey = $this->cooldownKey($user->id);
        if (Cache::has($cooldownKey)) {
            throw ValidationException::withMessages([
                'email_otp' => 'Please wait before requesting another code.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        Cache::put($this->codeKey($user->id), hash('sha256', $code), self::TTL_SECONDS);
        Cache::put($cooldownKey, true, self::COOLDOWN_SECONDS);

        try {
            Mail::to($user->email)->send(new WithdrawalOtpMail(
                name: $user->name ?: 'User',
                code: $code,
            ));
        } catch (\Throwable $e) {
            Cache::forget($this->codeKey($user->id));
            Cache::forget($cooldownKey);

            throw $e;
        }

        if (config('app.debug')) {
            Log::info('Withdrawal OTP sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => $code,
            ]);
        }
    }

    public function assertValid(User $user, ?string $code): void
    {
        $code = trim((string) $code);

        if ($code === '' || ! preg_match('/^\d{6}$/', $code)) {
            throw ValidationException::withMessages([
                'email_otp' => 'Enter the 6-digit verification code sent to your email.',
            ]);
        }

        $stored = Cache::get($this->codeKey($user->id));

        if (! is_string($stored) || ! hash_equals($stored, hash('sha256', $code))) {
            throw ValidationException::withMessages([
                'email_otp' => 'Invalid or expired verification code. Request a new code and try again.',
            ]);
        }
    }

    public function consume(User $user): void
    {
        Cache::forget($this->codeKey($user->id));
    }

    private function codeKey(int $userId): string
    {
        return "withdraw_otp:{$userId}";
    }

    private function cooldownKey(int $userId): string
    {
        return "withdraw_otp_cooldown:{$userId}";
    }
}
