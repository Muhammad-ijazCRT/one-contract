<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PlatformFeeService
{
    public function __construct(
        private readonly MainWalletService $mainWallet,
    ) {}

    public function rate(): float
    {
        return PlatformSettings::platformFeeRate();
    }

    public function calculate(float $rewardAmount): float
    {
        if ($rewardAmount <= 0) {
            return 0.0;
        }

        return round($rewardAmount * $this->rate(), 8);
    }

    /**
     * Deduct platform fee from Main Wallet. Fee is never taken from the reward itself.
     *
     * @throws ValidationException
     */
    public function collectFromMainWallet(User $user, float $rewardAmount, $reference = null, ?string $description = null): float
    {
        $fee = $this->calculate($rewardAmount);

        if ($fee <= 0) {
            return 0.0;
        }

        $balance = $this->mainWallet->balance($user);

        if ($balance < $fee) {
            throw ValidationException::withMessages([
                'platform_fee' => 'Insufficient Main Wallet balance for the Platform Fee. You need '
                    .number_format($fee, 2).' USDT in your Main Wallet to claim this reward.',
            ]);
        }

        $this->mainWallet->debit(
            $user,
            $fee,
            TransactionType::PlatformFee,
            $description ?? ('Platform Fee ('.PlatformSettings::platformFeePercent().'%)'),
            $reference,
        );

        return $fee;
    }
}
