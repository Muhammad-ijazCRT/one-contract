<?php

namespace App\Http\Controllers\User;

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\WalletConversion;
use App\Services\MultiWalletService;
use App\Services\WalletConversionService;
use App\Services\WalletLiveRateService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        private readonly MultiWalletService $wallets,
        private readonly WalletConversionService $conversions,
        private readonly WalletLiveRateService $liveRates,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $this->wallets->ensureWallets($user);

        $rates = $this->liveRates->displayPayload();
        $cards = collect($this->wallets->walletCards($user))
            ->map(function (array $card) {
                $usd = $this->liveRates->amountToUsd($card['currency'], (float) $card['balance']);
                $card['usd'] = $usd;
                $card['usd_formatted'] = '$'.number_format($usd, 2);

                return $card;
            })
            ->values()
            ->all();

        $totalUsd = collect($cards)->sum('usd');

        return view('user.wallets.index', [
            'user' => $user,
            'cards' => $cards,
            'totalUsd' => $totalUsd,
            'totalUsdt' => $totalUsd,
            'conversions' => WalletConversion::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(15)
                ->get(),
            'transactions' => $this->recentTransactions($user->id),
            'rates' => $rates,
            'feePercent' => $this->conversions->platformFeePercent(),
            'ratesUrl' => route('user.wallets.rates'),
        ]);
    }

    public function convertForm(): View
    {
        $user = auth()->user();
        $this->wallets->ensureWallets($user);
        $balances = $this->wallets->balances($user);
        $cryptoCards = collect(WalletCurrency::crypto())->map(function (WalletCurrency $currency) use ($balances) {
            $balance = (float) ($balances[$currency->value] ?? 0);

            return [
                'currency' => $currency,
                'code' => $currency->value,
                'label' => $currency->label(),
                'balance' => $balance,
                'formatted' => $currency->formatCompact($balance),
                'accent' => $currency->accentColor(),
                'icon' => $currency->iconUrl(),
            ];
        })->values()->all();

        $cryptoCodes = WalletCurrency::crypto();
        $cryptoCodeList = array_map(fn (WalletCurrency $c) => $c->value, $cryptoCodes);

        $recentConversions = WalletConversion::query()
            ->where('user_id', $user->id)
            ->whereIn('from_currency', $cryptoCodeList)
            ->whereIn('to_currency', $cryptoCodeList)
            ->latest()
            ->limit(5)
            ->get();

        return view('user.wallets.convert', [
            'cryptoCards' => $cryptoCards,
            'balancesJson' => collect($cryptoCards)->mapWithKeys(fn (array $c) => [$c['code'] => $c['balance']])->all(),
            'currencies' => WalletCurrency::crypto(),
            'feePercent' => $this->conversions->platformFeePercent(),
            'rates' => $this->liveRates->displayPayload(),
            'ratesUrl' => route('user.wallets.rates'),
            'recentConversions' => $recentConversions,
            'fromDefault' => old('from_currency', request('from', 'USDT')),
            'toDefault' => old('to_currency', request('to', 'ETH')),
        ]);
    }

    public function rates(Request $request): JsonResponse
    {
        if ($request->boolean('refresh')) {
            $this->liveRates->refresh();
        }

        return response()->json($this->liveRates->displayPayload());
    }

    public function convert(Request $request): RedirectResponse
    {
        $cryptoRule = WalletCurrency::cryptoCodes();
        $data = $request->validate([
            'from_currency' => ['required', 'string', 'in:'.$cryptoRule],
            'to_currency' => ['required', 'string', 'in:'.$cryptoRule, 'different:from_currency'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $from = WalletCurrency::normalize($data['from_currency']);
        $to = WalletCurrency::normalize($data['to_currency']);

        $this->conversions->convert(auth()->user(), $from, $to, (float) $data['amount']);

        return redirect()
            ->route('user.wallets.convert')
            ->with('success', 'Conversion completed successfully.');
    }

    public function quote(Request $request)
    {
        $cryptoRule = WalletCurrency::cryptoCodes();
        $data = $request->validate([
            'from_currency' => ['required', 'string', 'in:'.$cryptoRule],
            'to_currency' => ['required', 'string', 'in:'.$cryptoRule, 'different:from_currency'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $from = WalletCurrency::normalize($data['from_currency']);
        $to = WalletCurrency::normalize($data['to_currency']);
        $quote = $this->conversions->quote($from, $to, (float) $data['amount']);
        $payload = $this->liveRates->displayPayload();

        return response()->json([
            ...$quote,
            'from_formatted' => $from->format((float) $data['amount']),
            'to_formatted' => $to->format($quote['to_amount']),
            'fee_formatted' => $from->format($quote['fee_amount']),
            'provider_fee_formatted' => $from->format($quote['provider_fee_amount']),
            'platform_fee_formatted' => $from->format($quote['platform_fee_amount']),
            'provider_fee_percent' => $quote['provider_fee_percent'],
            'platform_fee_percent' => $quote['platform_fee_percent'],
            'total_fee_percent' => $quote['fee_percent'],
            'source' => $quote['source'] ?? null,
            'provider' => $quote['provider'] ?? 'nowpayments',
            'live' => $quote['live'] ?? false,
            'rate_display' => '1 '.$from->value.' = '.number_format($quote['rate'], 8).' '.$to->value,
            'fee_display' => 'NOWPayments '.number_format($quote['provider_fee_percent'], 2).'% + One-Contract '.number_format($quote['platform_fee_percent'], 2).'% = '.number_format($quote['fee_percent'], 2).'% total',
            'age_seconds' => $payload['age_seconds'] ?? 0,
        ]);
    }

    public function selectCurrency(): RedirectResponse
    {
        return redirect()->route('user.dashboard');
    }

    public function setPreferredCurrency(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'currency' => ['required', 'string', 'in:'.WalletCurrency::allCodes()],
        ]);

        $user = auth()->user();
        $currency = WalletCurrency::normalize($data['currency']);
        $user->setPreferredWalletCurrency($currency);

        $balance = $this->wallets->balance($user, $currency);
        $usdOfSelected = ($currency === WalletCurrency::Usdt || $currency === WalletCurrency::Usdc)
            ? $balance
            : $this->liveRates->amountToUsd($currency, $balance);
        $formatted = '$'.number_format($usdOfSelected, 2);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'currency' => $currency->value,
                'balance' => $balance,
                'formatted' => $formatted,
                'message' => $currency->value.' set as your active gaming currency.',
                'deposit_url' => $currency->isCrypto()
                    ? route('user.deposit.index', ['currency' => $currency->value])
                    : route('user.deposit.index', ['currency' => 'USDT']),
            ]);
        }

        // Always return to dashboard — never send users to convert/deposit from this picker.
        return redirect()
            ->route('user.dashboard')
            ->with('success', $currency->value.' set as your active gaming currency.');
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function recentTransactions(int $userId): LengthAwarePaginator
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->latest()
            ->paginate(5)
            ->withQueryString()
            ->fragment('ocmw-recent')
            ->through(function (Transaction $tx) {
                $amount = (float) $tx->amount;
                $currency = strtoupper((string) ($tx->metadata['currency'] ?? 'USDT'));
                $kind = $this->transactionKind($tx->type, $amount);
                $network = (string) ($tx->metadata['network'] ?? ($currency === 'USDT' ? 'TRC20' : $currency));

                return [
                    'id' => $tx->id,
                    'kind' => $kind,
                    'title' => match ($kind) {
                        'deposit' => 'Deposit',
                        'withdraw' => 'Withdrawal',
                        default => 'Conversion',
                    },
                    'network' => $currency.' — '.$network,
                    'amount' => $amount,
                    'amount_label' => ($amount >= 0 ? '+' : '').number_format($amount, abs($amount) >= 1 ? 2 : 6).' '.$currency,
                    'usd_label' => '$'.number_format(abs($amount), 2),
                    'status' => ucfirst((string) ($tx->status ?? 'completed')),
                    'at' => $tx->created_at?->format('M j, Y — g:i A'),
                    'url' => route('user.transactions.show', $tx),
                ];
            });
    }

    private function transactionKind(TransactionType $type, float $amount): string
    {
        return match ($type) {
            TransactionType::Deposit,
            TransactionType::CryptoDeposit,
            TransactionType::FiatOnRamp,
            TransactionType::MainWalletCredit,
            TransactionType::WalletConvertIn => 'deposit',
            TransactionType::WalletConvertOut => 'convert',
            TransactionType::RewardWithdrawal,
            TransactionType::CapitalWithdrawal,
            TransactionType::CryptoWithdrawal,
            TransactionType::FiatOffRampHold,
            TransactionType::MainWalletDebit => 'withdraw',
            default => $amount >= 0 ? 'deposit' : 'withdraw',
        };
    }
}
