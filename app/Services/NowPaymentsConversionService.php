<?php

namespace App\Services;

use App\Enums\WalletCurrency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NowPaymentsConversionService
{
    public function __construct(
        private readonly NowPaymentsClient $client,
        private readonly WalletLiveRateService $liveRates,
    ) {}

    public function platformFeePercent(): float
    {
        return (float) config('wallets.platform_conversion_fee_percent', 1.0);
    }

    /**
     * Dynamic NOWPayments provider fee for a pair (not hard-coded).
     */
    public function providerFeePercent(WalletCurrency $from, WalletCurrency $to): float
    {
        $cacheKey = sprintf(
            'nowpayments.provider_fee.%s.%s.%s',
            NowPaymentsSettings::mode(),
            $from->value,
            $to->value,
        );

        return Cache::remember($cacheKey, now()->addMinutes(3), function () use ($from, $to) {
            $spotRate = $this->spotRate($from, $to);

            if ($spotRate <= 0) {
                throw ValidationException::withMessages([
                    'rate' => 'Live conversion rate unavailable. Try again.',
                ]);
            }

            if (! $this->client->isConfigured()) {
                throw ValidationException::withMessages([
                    'conversion' => 'NOWPayments is not configured for conversions.',
                ]);
            }

            $estimate = $this->client->estimate(
                1.0,
                $from->nowPaymentsCurrency(),
                $to->nowPaymentsCurrency(),
            );

            $npRate = (float) ($estimate['estimated_amount'] ?? 0);

            if ($npRate <= 0) {
                throw ValidationException::withMessages([
                    'conversion' => 'NOWPayments conversion estimate unavailable for this pair.',
                ]);
            }

            $feePercent = max(0, round((1 - ($npRate / $spotRate)) * 100, 4));

            return $feePercent;
        });
    }

    /**
     * @return array{
     *     rate: float,
     *     provider_fee_percent: float,
     *     provider_fee_amount: float,
     *     platform_fee_percent: float,
     *     platform_fee_amount: float,
     *     fee_percent: float,
     *     fee_amount: float,
     *     to_amount: float,
     *     net_from: float,
     *     source: string,
     *     fetched_at: string,
     *     live: bool,
     *     provider: string
     * }
     */
    public function quote(WalletCurrency $from, WalletCurrency $to, float $amount): array
    {
        if ($from === $to) {
            throw ValidationException::withMessages(['to_currency' => 'Choose a different target currency.']);
        }

        $min = (float) (config('wallets.min_convert.'.$from->value) ?? 0);
        if ($amount < $min) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum convert amount is '.$from->format($min).'.',
            ]);
        }

        $providerPercent = $this->providerFeePercent($from, $to);
        $platformPercent = $this->platformFeePercent();
        $totalPercent = $providerPercent + $platformPercent;

        $providerFee = round($amount * ($providerPercent / 100), 8);
        $platformFee = round($amount * ($platformPercent / 100), 8);
        $totalFee = round($providerFee + $platformFee, 8);
        $netFrom = round($amount - $totalFee, $from->decimals());

        if ($netFrom <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount too small after fees.']);
        }

        $estimate = $this->client->estimate(
            $netFrom,
            $from->nowPaymentsCurrency(),
            $to->nowPaymentsCurrency(),
        );

        $toAmount = round((float) ($estimate['estimated_amount'] ?? 0), $to->decimals());

        if ($toAmount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Converted amount too small.']);
        }

        $rate = $netFrom > 0 ? $toAmount / $netFrom : 0.0;
        $rates = $this->liveRates->usdRates();

        return [
            'rate' => $rate,
            'provider_fee_percent' => $providerPercent,
            'provider_fee_amount' => $providerFee,
            'platform_fee_percent' => $platformPercent,
            'platform_fee_amount' => $platformFee,
            'fee_percent' => $totalPercent,
            'fee_amount' => $totalFee,
            'to_amount' => $toAmount,
            'net_from' => $netFrom,
            'source' => 'nowpayments',
            'fetched_at' => (string) ($rates['fetched_at'] ?? now()->toIso8601String()),
            'live' => true,
            'provider' => 'nowpayments',
        ];
    }

    /**
     * @return array{id: int|string|null, status: string, raw: array<string, mixed>}
     */
    public function executeConversion(WalletCurrency $from, WalletCurrency $to, float $netAmount): array
    {
        if (! $this->client->custodyConfigured()) {
            throw ValidationException::withMessages([
                'conversion' => 'NOWPayments Custody is not configured. Add custody email and password in admin payment settings.',
            ]);
        }

        $response = $this->client->createConversion(
            $netAmount,
            $from->nowPaymentsCurrency(),
            $to->nowPaymentsCurrency(),
        );

        $conversion = $this->extractConversionRecord($response);

        Log::info('NOWPayments conversion created', [
            'from' => $from->value,
            'to' => $to->value,
            'amount' => $netAmount,
            'conversion_id' => $conversion['id'],
            'status' => $conversion['status'],
        ]);

        return $conversion;
    }

    /**
     * @return array{id: int|string|null, status: string, to_amount: float|null, raw: array<string, mixed>}
     */
    public function syncConversion(int|string $conversionId): array
    {
        $response = $this->client->getConversion($conversionId);
        $record = $this->extractConversionRecord($response);

        $toAmount = isset($record['raw']['to_amount'])
            ? (float) $record['raw']['to_amount']
            : (isset($record['raw']['amount_to']) ? (float) $record['raw']['amount_to'] : null);

        return [
            'id' => $record['id'],
            'status' => $record['status'],
            'to_amount' => $toAmount,
            'raw' => $record['raw'],
        ];
    }

    public function isFinishedStatus(string $status): bool
    {
        return in_array(strtolower($status), ['finished', 'completed', 'success', 'done'], true);
    }

    public function isFailedStatus(string $status): bool
    {
        return in_array(strtolower($status), ['failed', 'rejected', 'error', 'cancelled', 'canceled'], true);
    }

    public function waitForCompletion(int|string $conversionId, int $attempts = 8, int $sleepMs = 1500): array
    {
        $last = null;

        for ($i = 0; $i < $attempts; $i++) {
            $last = $this->syncConversion($conversionId);

            if ($this->isFinishedStatus($last['status'])) {
                return $last;
            }

            if ($this->isFailedStatus($last['status'])) {
                throw ValidationException::withMessages([
                    'conversion' => 'NOWPayments conversion failed ('.$last['status'].').',
                ]);
            }

            usleep($sleepMs * 1000);
        }

        return $last ?? ['id' => $conversionId, 'status' => 'processing', 'to_amount' => null, 'raw' => []];
    }

    private function spotRate(WalletCurrency $from, WalletCurrency $to): float
    {
        $rates = $this->liveRates->usdRates();
        $fromUsd = (float) ($rates[$from->value] ?? 0);
        $toUsd = (float) ($rates[$to->value] ?? 0);

        if ($fromUsd <= 0 || $toUsd <= 0) {
            return 0.0;
        }

        return $fromUsd / $toUsd;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array{id: int|string|null, status: string, raw: array<string, mixed>}
     */
    private function extractConversionRecord(array $response): array
    {
        $raw = $response;

        if (isset($response['result']) && is_array($response['result'])) {
            $raw = $response['result'];
        } elseif (isset($response['conversions'][0]) && is_array($response['conversions'][0])) {
            $raw = $response['conversions'][0];
        }

        return [
            'id' => $raw['id'] ?? $raw['conversion_id'] ?? null,
            'status' => strtolower((string) ($raw['status'] ?? 'processing')),
            'raw' => $raw,
        ];
    }
}
