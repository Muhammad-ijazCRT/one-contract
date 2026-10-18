<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Legacy Main Wallet API — now backed by USDT multi-wallet (keeps clubs/games working).
 */
class MainWalletService
{
    public function __construct(
        private readonly MultiWalletService $wallets,
    ) {}

    public function balance(User $user): float
    {
        return $this->wallets->balance($user, WalletCurrency::Usdt);
    }

    public function credit(
        User $user,
        float $amount,
        TransactionType $type,
        string $description,
        ?Model $reference = null,
        array $metadata = []
    ): Transaction {
        return $this->wallets->credit(
            $user,
            WalletCurrency::Usdt,
            $amount,
            $type,
            $description,
            $reference,
            array_merge($metadata, ['wallet' => 'main'])
        );
    }

    public function debit(
        User $user,
        float $amount,
        TransactionType $type,
        string $description,
        ?Model $reference = null,
        array $metadata = []
    ): Transaction {
        return $this->wallets->debit(
            $user,
            WalletCurrency::Usdt,
            $amount,
            $type,
            $description,
            $reference,
            array_merge($metadata, ['wallet' => 'main'])
        );
    }
}
