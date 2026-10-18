<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\FiatRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FiatRequestService
{
    public function __construct(
        private readonly MultiWalletService $wallets,
    ) {}

    private function resolveCurrency(string $raw): WalletCurrency
    {
        $raw = strtoupper(trim($raw));

        return match ($raw) {
            'NGN', 'NAIRA' => WalletCurrency::Ngn,
            'USD', 'USDT' => WalletCurrency::Usdt,
            default => WalletCurrency::tryNormalize($raw) ?? WalletCurrency::Usdt,
        };
    }

    public function submitOnRamp(User $user, array $data): FiatRequest
    {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount < 10) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum fiat deposit is 10.00.',
            ]);
        }

        $currency = $this->resolveCurrency((string) ($data['currency'] ?? 'USD'));
        if ($currency->isCrypto() && $currency !== WalletCurrency::Usdt) {
            throw ValidationException::withMessages([
                'currency' => 'Fiat on-ramp supports USD/USDT or NGN only.',
            ]);
        }

        return FiatRequest::query()->create([
            'user_id' => $user->id,
            'direction' => 'on_ramp',
            'amount' => $amount,
            'currency' => $currency->value,
            'bank_name' => $data['bank_name'] ?? null,
            'account_name' => $data['account_name'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);
    }

    public function submitOffRamp(User $user, array $data): FiatRequest
    {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount < 10) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum fiat withdrawal is 10.00.',
            ]);
        }

        $currency = $this->resolveCurrency((string) ($data['currency'] ?? 'USD'));
        if ($currency->isCrypto() && $currency !== WalletCurrency::Usdt) {
            throw ValidationException::withMessages([
                'currency' => 'Fiat off-ramp supports USD/USDT or NGN only.',
            ]);
        }

        return DB::transaction(function () use ($user, $data, $amount, $currency) {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($this->wallets->balance($user, $currency) < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient '.$currency->value.' balance.',
                ]);
            }

            $request = FiatRequest::query()->create([
                'user_id' => $user->id,
                'direction' => 'off_ramp',
                'amount' => $amount,
                'currency' => $currency->value,
                'bank_name' => $data['bank_name'] ?? null,
                'account_name' => $data['account_name'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);

            $this->wallets->debit(
                $user,
                $currency,
                $amount,
                TransactionType::FiatOffRampHold,
                'Fiat withdrawal hold #'.$request->id,
                $request,
            );

            return $request;
        });
    }

    public function approveOnRamp(FiatRequest $request, User $admin): FiatRequest
    {
        if ($request->direction !== 'on_ramp' || $request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Request cannot be approved.']);
        }

        return DB::transaction(function () use ($request, $admin) {
            $request = FiatRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            $user = User::query()->whereKey($request->user_id)->lockForUpdate()->firstOrFail();
            $currency = $this->resolveCurrency((string) $request->currency);

            $this->wallets->credit(
                $user,
                $currency,
                (float) $request->amount,
                TransactionType::FiatOnRamp,
                'Fiat deposit #'.$request->id.' → '.$currency->value,
                $request,
            );

            $request->update([
                'status' => 'completed',
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            return $request->fresh();
        });
    }

    public function completeOffRamp(FiatRequest $request, User $admin): FiatRequest
    {
        if ($request->direction !== 'off_ramp' || $request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Request cannot be completed.']);
        }

        $request->update([
            'status' => 'completed',
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        return $request->fresh();
    }

    public function reject(FiatRequest $request, User $admin, ?string $notes = null): FiatRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Request cannot be rejected.']);
        }

        return DB::transaction(function () use ($request, $admin, $notes) {
            $request = FiatRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($request->direction === 'off_ramp') {
                $user = User::query()->whereKey($request->user_id)->lockForUpdate()->firstOrFail();
                $currency = $this->resolveCurrency((string) $request->currency);
                $this->wallets->credit(
                    $user,
                    $currency,
                    (float) $request->amount,
                    TransactionType::FiatOffRampRefund,
                    'Fiat withdrawal refund #'.$request->id,
                    $request,
                );
            }

            $request->update([
                'status' => 'rejected',
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'admin_notes' => $notes,
            ]);

            return $request->fresh();
        });
    }
}
