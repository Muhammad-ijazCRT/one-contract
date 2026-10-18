<?php

namespace App\Services;

use App\Enums\WalletCurrency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Live market rates — CoinGecko (crypto) + fallbacks.
 * Values are "1 unit of currency → USD" for internal quoting.
 */
class WalletLiveRateService
{
    /**
     * @return array<string, mixed>
     */
    public function usdRates(): array
    {
        $ttl = max(1, (int) config('wallets.live_rate_cache_seconds', 5));

        return Cache::remember('wallet.live_usd_rates.v5', $ttl, function () {
            return $this->fetchLiveUsdRates();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function refresh(): array
    {
        foreach (['v5', 'v4', 'v3', 'v2', 'v1'] as $ver) {
            Cache::forget("wallet.live_usd_rates.{$ver}");
        }

        return $this->usdRates();
    }

    public function usdPerUnit(WalletCurrency $currency): float
    {
        if ($currency === WalletCurrency::Usdt || $currency === WalletCurrency::Usdc) {
            return 1.0;
        }

        $rates = $this->usdRates();
        $rate = (float) ($rates[$currency->value] ?? 0);

        if ($rate <= 0) {
            $rate = (float) (config('wallets.usd_rates.'.$currency->value) ?? 0);
        }

        return $rate;
    }

    public function amountToUsd(WalletCurrency $currency, float $amount): float
    {
        if ($amount == 0.0) {
            return 0.0;
        }

        $usdPerUnit = $this->usdPerUnit($currency);

        return $usdPerUnit > 0 ? $amount * $usdPerUnit : 0.0;
    }

    /**
     * @param  array<string, float>|Collection<string, float>  $balances
     */
    public function portfolioUsd(array|Collection $balances): float
    {
        $total = 0.0;

        foreach ($balances as $code => $amount) {
            $currency = WalletCurrency::tryNormalize((string) $code);

            if (! $currency) {
                continue;
            }

            $total += $this->amountToUsd($currency, (float) $amount);
        }

        return round($total, 2);
    }

    /**
     * How much crypto equals the given USD amount at the live rate.
     */
    public function cryptoAmountForUsd(WalletCurrency $currency, float $usd): float
    {
        if ($usd <= 0) {
            return 0.0;
        }

        $usdPerUnit = $this->usdPerUnit($currency);

        if ($usdPerUnit <= 0) {
            $usdPerUnit = (float) (config('wallets.usd_rates.'.$currency->value) ?? 0);
        }

        if ($usdPerUnit <= 0) {
            return 0.0;
        }

        return round($usd / $usdPerUnit, $currency->decimals());
    }

    /**
     * Payload for wallets / convert UI.
     *
     * @return array<string, mixed>
     */
    public function displayPayload(): array
    {
        $rates = $this->usdRates();
        $pairs = [];
        $display = [];

        foreach (WalletCurrency::crypto() as $currency) {
            $usd = (float) ($rates[$currency->value] ?? 0);
            $key = $currency->value.'_USD';
            $pairs[$key] = $usd;
            $display[$key] = $usd > 0
                ? '$'.number_format($usd, $usd >= 1 ? 2 : 6)
                : '—';
        }

        $ngnUsd = (float) ($rates['NGN'] ?? 0);
        $usdNgn = $ngnUsd > 0 ? (1 / $ngnUsd) : 0.0;
        $pairs['USD_NGN'] = $usdNgn;
        $pairs['USDT_NGN'] = (float) ($rates['USDT_NGN'] ?? ($usdNgn > 0 ? $usdNgn : 0));
        $display['USD_NGN'] = $usdNgn > 0 ? '1 USD ≈ '.number_format($usdNgn, 2).' NGN' : '—';
        $display['USDT_NGN'] = ($pairs['USDT_NGN'] ?? 0) > 0
            ? '1 USDT ≈ '.number_format((float) $pairs['USDT_NGN'], 2).' NGN'
            : '—';

        $fetchedAt = (string) ($rates['fetched_at'] ?? now()->toIso8601String());
        $age = max(0, now()->diffInSeconds(\Illuminate\Support\Carbon::parse($fetchedAt)));

        return [
            'source' => (string) ($rates['source'] ?? 'live'),
            'fetched_at' => $fetchedAt,
            'age_seconds' => $age,
            'refresh_seconds' => max(1, (int) config('wallets.live_rate_cache_seconds', 5)),
            'pairs' => $pairs,
            'display' => $display,
            'balances_keyed' => collect(WalletCurrency::crypto())
                ->mapWithKeys(fn (WalletCurrency $c) => [$c->value => (float) ($rates[$c->value] ?? 0)])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchLiveUsdRates(): array
    {
        $result = $this->fallbackRates();
        $sources = [];

        $crypto = $this->fetchCoinGecko();
        if ($crypto !== null) {
            foreach ($crypto as $code => $usd) {
                if (is_string($code) && strlen($code) <= 6 && $usd > 0) {
                    $result[$code] = $usd;
                }
            }
            if (($crypto['USDT_NGN'] ?? 0) > 0 && ($result['USDT'] ?? 0) > 0) {
                $result['NGN'] = $result['USDT'] / $crypto['USDT_NGN'];
                $result['USDT_NGN'] = $crypto['USDT_NGN'];
            }
            $sources[] = 'CoinGecko';
        }

        if (($result['NGN'] ?? 0) <= 0) {
            $openNgn = $this->fetchOpenExchangeUsdNgn();
            if ($openNgn !== null && $openNgn > 0) {
                $result['NGN'] = 1 / $openNgn;
                $sources[] = 'ExchangeRate API';
            }
        }

        $result['USDT'] = ($result['USDT'] ?? 0) > 0 ? (float) $result['USDT'] : 1.0;
        $result['source'] = $sources !== [] ? implode(' + ', $sources) : 'config fallback';
        $result['fetched_at'] = now()->toIso8601String();

        return $result;
    }

    /**
     * @return array<string, float>|null
     */
    private function fetchCoinGecko(): ?array
    {
        $ids = [];
        $idToCode = [];
        foreach (WalletCurrency::crypto() as $currency) {
            $id = $currency->coingeckoId();
            if ($id) {
                $ids[] = $id;
                $idToCode[$id] = $currency->value;
            }
        }

        if ($ids === []) {
            return null;
        }

        try {
            $response = Http::timeout(12)
                ->acceptJson()
                ->get('https://api.coingecko.com/api/v3/simple/price', [
                    'ids' => implode(',', array_unique($ids)),
                    'vs_currencies' => 'usd,ngn',
                ]);

            if (! $response->successful()) {
                Log::warning('CoinGecko rates failed', ['status' => $response->status()]);

                return null;
            }

            $data = $response->json();
            if (! is_array($data)) {
                return null;
            }

            $out = [];
            foreach ($idToCode as $geckoId => $code) {
                $usd = (float) ($data[$geckoId]['usd'] ?? 0);
                if ($usd > 0) {
                    $out[$code] = $usd;
                }
            }

            $usdtNgn = (float) ($data['tether']['ngn'] ?? 0);
            if ($usdtNgn > 0) {
                $out['USDT_NGN'] = $usdtNgn;
            }

            return count($out) > 0 ? $out : null;
        } catch (\Throwable $e) {
            Log::warning('CoinGecko rates exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function fetchOpenExchangeUsdNgn(): ?float
    {
        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get('https://open.er-api.com/v6/latest/USD');

            if (! $response->successful()) {
                return null;
            }

            $ngn = (float) ($response->json('rates.NGN') ?? 0);

            return $ngn > 100 ? $ngn : null;
        } catch (\Throwable $e) {
            Log::warning('Open ER API failed', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return array<string, float>
     */
    private function fallbackRates(): array
    {
        $cfg = config('wallets.usd_rates', []);
        $out = [];
        foreach (WalletCurrency::all() as $currency) {
            $out[$currency->value] = (float) ($cfg[$currency->value] ?? 0);
        }

        return $out;
    }
}
