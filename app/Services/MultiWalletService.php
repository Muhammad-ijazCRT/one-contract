<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MultiWalletService
{
    public function ensureWallets(User $user): void
    {
        foreach (WalletCurrency::all() as $currency) {
            UserWallet::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'currency' => $currency->value,
                ],
                ['balance' => 0]
            );
        }
    }

    public function balance(User $user, WalletCurrency|string $currency): float
    {
        $currency = $currency instanceof WalletCurrency ? $currency : WalletCurrency::normalize($currency);
        $this->ensureWallets($user);

        return (float) UserWallet::query()
            ->where('user_id', $user->id)
            ->where('currency', $currency->value)
            ->value('balance');
    }

    /**
     * @return Collection<string, float> keyed by currency code
     */
    public function balances(User $user): Collection
    {
        $this->ensureWallets($user);

        return UserWallet::query()
            ->where('user_id', $user->id)
            ->get()
            ->mapWithKeys(fn (UserWallet $w) => [
                ($w->currency instanceof WalletCurrency ? $w->currency->value : (string) $w->currency) => (float) $w->balance,
            ]);
    }

    /**
     * @return list<array{currency: WalletCurrency, balance: float, label: string, formatted: string}>
     */
    public function walletCards(User $user): array
    {
        $balances = $this->balances($user);
        $cards = [];

        foreach (WalletCurrency::all() as $currency) {
            $balance = (float) ($balances[$currency->value] ?? 0);
            $cards[] = [
                'currency' => $currency,
                'code' => $currency->value,
                'label' => $currency->label(),
                'balance' => $balance,
                'formatted' => $currency->format($balance),
                'symbol' => $currency->symbol(),
                'icon' => $currency->iconUrl(),
            ];
        }

        return $cards;
    }

    public function credit(
        User $user,
        WalletCurrency|string $currency,
        float $amount,
        TransactionType $type,
        string $description,
        ?Model $reference = null,
        array $metadata = []
    ): Transaction {
        return $this->apply($user, $currency, abs($amount), 'credit', $type, $description, $reference, $metadata);
    }

    public function debit(
        User $user,
        WalletCurrency|string $currency,
        float $amount,
        TransactionType $type,
        string $description,
        ?Model $reference = null,
        array $metadata = []
    ): Transaction {
        return $this->apply($user, $currency, abs($amount), 'debit', $type, $description, $reference, $metadata);
    }

    private function apply(
        User $user,
        WalletCurrency|string $currency,
        float $amount,
        string $direction,
        TransactionType $type,
        string $description,
        ?Model $reference,
        array $metadata
    ): Transaction {
        $currency = $currency instanceof WalletCurrency ? $currency : WalletCurrency::normalize($currency);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Wallet amount must be greater than zero.');
        }

        return DB::transaction(function () use ($user, $currency, $amount, $direction, $type, $description, $reference, $metadata) {
            $this->ensureWallets($user);

            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $wallet = UserWallet::query()
                ->where('user_id', $lockedUser->id)
                ->where('currency', $currency->value)
                ->lockForUpdate()
                ->firstOrFail();

            $current = (float) $wallet->balance;

            if ($direction === 'debit' && $current < $amount) {
                throw new InvalidArgumentException('Insufficient '.$currency->value.' wallet balance.');
            }

            $next = $direction === 'credit' ? $current + $amount : $current - $amount;
            $wallet->balance = $next;
            $wallet->save();

            // Keep legacy columns in sync for existing product code
            if ($currency === WalletCurrency::Usdt) {
                $lockedUser->main_wallet_balance = $next;
                $lockedUser->save();
            } elseif ($currency === WalletCurrency::Ngn) {
                $lockedUser->ngn_wallet_balance = $next;
                $lockedUser->save();
            }

            return Transaction::query()->create([
                'user_id' => $lockedUser->id,
                'type' => $type,
                'amount' => $direction === 'debit' ? -$amount : $amount,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'description' => $description,
                'status' => 'completed',
                'metadata' => array_merge($metadata, [
                    'wallet' => strtolower($currency->value),
                    'currency' => $currency->value,
                    'direction' => $direction,
                    'balance_after' => $next,
                ]),
            ]);
        });
    }
}
