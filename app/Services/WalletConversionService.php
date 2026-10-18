<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\User;
use App\Models\WalletConversion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletConversionService
{
    public function __construct(
        private readonly MultiWalletService $wallets,
        private readonly NotificationService $notifications,
        private readonly WalletLiveRateService $liveRates,
        private readonly NowPaymentsConversionService $nowPayments,
    ) {}

    public function platformFeePercent(): float
    {
        return $this->nowPayments->platformFeePercent();
    }

    /** @deprecated Use platformFeePercent() */
    public function feePercent(): float
    {
        return $this->platformFeePercent();
    }

    /**
     * @return array{BTC: float, USDT: float, TRX: float, NGN: float, source: string, fetched_at: string}
     */
    public function currentRates(): array
    {
        return $this->liveRates->usdRates();
    }

    /**
     * @return array<string, mixed>
     */
    public function quote(WalletCurrency $from, WalletCurrency $to, float $amount): array
    {
        return $this->nowPayments->quote($from, $to, $amount);
    }

    public function convert(User $user, WalletCurrency $from, WalletCurrency $to, float $amount): WalletConversion
    {
        $amount = round($amount, $from->decimals());

        if (config('wallets.refresh_rate_on_convert', true)) {
            $this->liveRates->refresh();
        }

        $quote = $this->nowPayments->quote($from, $to, $amount);

        return DB::transaction(function () use ($user, $from, $to, $amount, $quote) {
            $this->wallets->debit(
                $user,
                $from,
                $amount,
                TransactionType::WalletConvertOut,
                'Convert '.$from->value.' → '.$to->value.' (NOWPayments)',
            );

            $conversion = WalletConversion::query()->create([
                'user_id' => $user->id,
                'from_currency' => $from->value,
                'to_currency' => $to->value,
                'from_amount' => $amount,
                'to_amount' => $quote['to_amount'],
                'rate' => $quote['rate'],
                'fee_amount' => $quote['fee_amount'],
                'fee_percent' => $quote['fee_percent'],
                'provider_fee_percent' => $quote['provider_fee_percent'],
                'provider_fee_amount' => $quote['provider_fee_amount'],
                'platform_fee_percent' => $quote['platform_fee_percent'],
                'platform_fee_amount' => $quote['platform_fee_amount'],
                'status' => 'processing',
                'metadata' => $quote,
            ]);

            try {
                $np = $this->nowPayments->executeConversion($from, $to, (float) $quote['net_from']);
                $finalToAmount = (float) $quote['to_amount'];
                $finalStatus = strtolower((string) ($np['status'] ?? 'processing'));
                $npMeta = ['create' => $np['raw'] ?? []];

                $conversion->update([
                    'nowpayments_conversion_id' => $np['id'] ? (string) $np['id'] : null,
                    'status' => $finalStatus,
                ]);

                if ($np['id']) {
                    $synced = $this->nowPayments->waitForCompletion($np['id']);
                    $npMeta['sync'] = $synced['raw'] ?? [];
                    $finalStatus = strtolower((string) ($synced['status'] ?? $finalStatus));

                    if ($synced['to_amount'] !== null && $synced['to_amount'] > 0) {
                        $finalToAmount = round($synced['to_amount'], $to->decimals());
                    }

                    if ($this->nowPayments->isFailedStatus($finalStatus)) {
                        throw ValidationException::withMessages([
                            'conversion' => 'NOWPayments conversion failed ('.$finalStatus.').',
                        ]);
                    }
                }

                if ($this->nowPayments->isFinishedStatus($finalStatus)) {
                    $finalStatus = 'completed';
                }

                $conversion->update([
                    'to_amount' => $finalToAmount,
                    'status' => $finalStatus,
                    'metadata' => array_merge($quote, ['nowpayments' => $npMeta]),
                ]);

                if ($finalStatus === 'completed') {
                    $this->wallets->credit(
                        $user,
                        $to,
                        $finalToAmount,
                        TransactionType::WalletConvertIn,
                        'Convert '.$from->value.' → '.$to->value.' via NOWPayments',
                        $conversion,
                    );

                    $this->notifications->notifyUser(
                        $user,
                        'wallet',
                        'Conversion Complete',
                        'Converted '.$from->format($amount).' to '.$to->format($finalToAmount)
                            .' (NOWPayments '.$quote['provider_fee_percent'].'% + platform '.$quote['platform_fee_percent'].'%).',
                        ['url' => route('user.wallets.convert')],
                    );
                } else {
                    $this->notifications->notifyUser(
                        $user,
                        'wallet',
                        'Conversion Processing',
                        'Your '.$from->value.' → '.$to->value.' conversion is processing via NOWPayments.',
                        ['url' => route('user.wallets.convert')],
                    );
                }

                return $conversion->fresh();
            } catch (\Throwable $e) {
                $this->wallets->credit(
                    $user,
                    $from,
                    $amount,
                    TransactionType::WalletConvertIn,
                    'Conversion refund '.$from->value.' (NOWPayments failed)',
                    $conversion,
                );

                $conversion->update([
                    'status' => 'failed',
                    'metadata' => array_merge($quote, [
                        'error' => $e->getMessage(),
                    ]),
                ]);

                throw $e instanceof ValidationException ? $e : ValidationException::withMessages([
                    'conversion' => $e->getMessage() ?: 'Conversion failed. Your funds were refunded.',
                ]);
            }
        });
    }
}
