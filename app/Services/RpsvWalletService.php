<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class RpsvWalletService
{
    public function __construct(
        private readonly MultiWalletService $wallets,
    ) {}

    public function balance(User $user, string $currency): float
    {
        return $this->wallets->balance($user, $this->toWalletCurrency($currency));
    }

    public function debit(User $user, string $currency, float $amount, TransactionType $type, string $description, ?Model $reference = null): Transaction
    {
        return $this->apply($user, $currency, $amount, 'debit', $type, $description, $reference);
    }

    public function credit(User $user, string $currency, float $amount, TransactionType $type, string $description, ?Model $reference = null): Transaction
    {
        return $this->apply($user, $currency, $amount, 'credit', $type, $description, $reference);
    }

    /**
     * Map RPSV currency codes (incl. legacy USD) onto wallet currencies.
     */
    public function toWalletCurrency(string $currency): WalletCurrency
    {
        $code = strtoupper(trim($currency));

        if ($code === 'USD') {
            return WalletCurrency::Usdt;
        }

        $resolved = WalletCurrency::tryNormalize($code);
        if (! $resolved) {
            throw ValidationException::withMessages(['currency' => 'Unsupported gaming currency.']);
        }

        return $resolved;
    }

    /**
     * Resolve the active gaming currency for RPSV (preferred wallet → match code).
     */
    public function activeMatchCurrency(User $user): string
    {
        $preferred = $user->preferredWalletCurrency();

        return $preferred === WalletCurrency::Usdt ? 'USD' : $preferred->value;
    }

    private function apply(
        User $user,
        string $currency,
        float $amount,
        string $direction,
        TransactionType $type,
        string $description,
        ?Model $reference
    ): Transaction {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        $walletCurrency = $this->toWalletCurrency($currency);

        try {
            return $direction === 'credit'
                ? $this->wallets->credit($user, $walletCurrency, $amount, $type, $description, $reference, [
                    'game' => 'rpsv',
                    'rpsv_currency' => strtoupper($currency),
                ])
                : $this->wallets->debit($user, $walletCurrency, $amount, $type, $description, $reference, [
                    'game' => 'rpsv',
                    'rpsv_currency' => strtoupper($currency),
                ]);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['amount' => $e->getMessage()]);
        }
    }
}
