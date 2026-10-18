<?php

namespace App\Http\Controllers\User;

use App\Enums\WalletCurrency;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreDepositRequest;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Services\DepositService;
use App\Services\PlatformSettings;
use App\Services\WalletLiveRateService;
use App\Support\DepositHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function __construct(
        private readonly DepositService $deposits,
        private readonly WalletLiveRateService $liveRates,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->boolean('cancelled')) {
            return redirect()
                ->route('user.deposit.index')
                ->with('info', 'Payment was cancelled. You can start a new deposit whenever you are ready.');
        }

        $user = auth()->user();

        $this->deposits->syncPendingDepositsForUser($user);

        $liveRates = $this->liveRates->displayPayload();

        return view('user.deposit.index', [
            'activePrincipal' => $user->activePrincipal(),
            'deposits' => DepositHistory::paginate($user),
            'pendingDepositsCount' => DepositHistory::pendingCount($user),
            'minDeposit' => PlatformSettings::gatewayMinDeposit(),
            'depositsEnabled' => PlatformSettings::depositsEnabled(),
            'platformFeePercent' => PlatformSettings::platformFeePercent(),
            'usdRates' => $liveRates['balances_keyed'] ?? [],
            'cryptoDecimals' => collect(WalletCurrency::crypto())
                ->mapWithKeys(fn (WalletCurrency $c) => [$c->value => $c->decimals()])
                ->all(),
            'ratesUrl' => route('user.wallets.rates'),
        ]);
    }

    public function store(StoreDepositRequest $request): RedirectResponse
    {
        if (! PlatformSettings::depositsEnabled()) {
            return redirect()
                ->route('user.deposit.index')
                ->with('error', 'Deposits are temporarily disabled by the platform. Please check back later.');
        }

        $deposit = $this->deposits->submit(
            auth()->user(),
            (float) $request->validated('amount'),
            (string) $request->validated('currency'),
            $request->validated('network'),
        );

        if ($deposit->payment_url) {
            return redirect()->away($deposit->payment_url);
        }

        if ($deposit->pay_address) {
            return redirect()
                ->route('user.deposit.show', $deposit)
                ->with('success', 'Send the exact crypto amount to the address below. Your wallet will be credited after confirmations.');
        }

        return redirect()
            ->route('user.deposit.index')
            ->with('error', 'Could not open the payment page. Please try again.');
    }

    public function paymentReturn(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $paymentId = trim((string) ($request->query('paymentId', $request->query('payment_id', $request->query('NP_id', '')))));
        $invoiceId = trim((string) ($request->query('iid', $request->query('invoice_id', ''))));
        $orderId = trim((string) $request->query('order_id', ''));

        $deposit = null;

        if ($orderId !== '') {
            $deposit = $user->deposits()->where('order_id', $orderId)->latest()->first();
        }

        if (! $deposit && $invoiceId !== '') {
            $deposit = $user->deposits()
                ->where(function ($query) use ($invoiceId) {
                    $query->where('purchase_id', $invoiceId)
                        ->orWhere('payment_id', $invoiceId);
                })
                ->latest()
                ->first();
        }

        if (! $deposit) {
            $deposit = $user->pendingDeposits()->whereNotNull('purchase_id')->latest()->first();
        }

        if ($deposit && $paymentId !== '') {
            $deposit = $this->deposits->attachGatewayPaymentId($deposit, $paymentId);
        }

        if ($deposit) {
            $this->deposits->syncDeposit($deposit);
        }

        return redirect()->route('user.deposit.index');
    }

    public function pending(): View
    {
        return view('user.deposit.pending', [
            'deposits' => DepositHistory::paginate(auth()->user(), true),
        ]);
    }

    public function show(Deposit $deposit): View
    {
        abort_unless($deposit->user_id === auth()->id(), 403);

        $this->deposits->syncDeposit($deposit->fresh());

        $txnMeta = Transaction::query()
            ->where('user_id', $deposit->user_id)
            ->where('reference_type', Deposit::class)
            ->where('reference_id', $deposit->id)
            ->value('metadata');

        $payAmount = is_array($txnMeta) ? ($txnMeta['pay_amount'] ?? null) : null;

        return view('user.deposit.show', [
            'deposit' => $deposit->fresh(),
            'payAmount' => $payAmount,
        ]);
    }
}
