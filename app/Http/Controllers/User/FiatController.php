<?php

namespace App\Http\Controllers\User;

use App\Enums\WalletCurrency;
use App\Http\Controllers\Controller;
use App\Models\FiatRequest;
use App\Services\FiatRequestService;
use App\Services\MonnifyService;
use App\Services\MonnifySettings;
use App\Services\MultiWalletService;
use App\Services\WalletLiveRateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiatController extends Controller
{
    public function __construct(
        private readonly FiatRequestService $fiat,
        private readonly MonnifyService $monnify,
        private readonly MultiWalletService $wallets,
        private readonly WalletLiveRateService $liveRates,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('user.fiat.index', [
            'balance' => $this->wallets->balance($user, WalletCurrency::Usdt),
            'ngnBalance' => $this->wallets->balance($user, WalletCurrency::Ngn),
            'walletCards' => $this->wallets->walletCards($user),
            'rates' => $this->liveRates->displayPayload(),
            'ratesUrl' => route('user.wallets.rates'),
            'monnifyEnabled' => MonnifySettings::isEnabled() && MonnifySettings::isConfigured(),
            'monnifyBanks' => MonnifySettings::isEnabled() ? $this->monnify->bankOptions() : [],
            'requests' => FiatRequest::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }

    public function storeOnRamp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:100'],
            'currency' => ['nullable', 'string', 'max:10'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $currency = strtoupper((string) ($data['currency'] ?? 'NGN'));

        if (MonnifySettings::depositsEnabled() && in_array($currency, ['NGN', 'NAIRA'], true)) {
            $fiatRequest = $this->monnify->initiateDeposit($request->user(), (float) $data['amount']);

            if ($fiatRequest->checkout_url) {
                return redirect()->away($fiatRequest->checkout_url);
            }

            return back()->with('error', 'Monnify checkout URL was not returned. Please try again.');
        }

        $data['bank_name'] = $data['bank_name'] ?? 'Manual transfer';
        $data['account_name'] = $data['account_name'] ?? $request->user()->name;
        $data['reference'] = $data['reference'] ?? 'MANUAL-'.now()->timestamp;

        $this->fiat->submitOnRamp($request->user(), $data);

        return back()->with('success', 'Fiat on-ramp submitted. Admin will credit after bank confirmation.');
    }

    public function storeOffRamp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:100'],
            'currency' => ['nullable', 'string', 'max:10'],
            'bank_name' => ['required', 'string', 'max:120'],
            'bank_code' => ['nullable', 'string', 'max:20'],
            'account_name' => ['required', 'string', 'max:120'],
            'account_number' => ['required', 'string', 'max:120'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $currency = strtoupper((string) ($data['currency'] ?? 'NGN'));

        if (MonnifySettings::withdrawalsEnabled() && in_array($currency, ['NGN', 'NAIRA'], true)) {
            $this->monnify->initiateWithdrawal($request->user(), $data);

            return back()->with('success', 'Naira withdrawal sent to Monnify. Status updates after payout confirmation.');
        }

        $this->fiat->submitOffRamp($request->user(), $data);

        return back()->with('success', 'Fiat off-ramp submitted. Balance held pending admin payout.');
    }
}
