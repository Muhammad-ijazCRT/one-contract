<?php

namespace App\Http\Controllers\User;

use App\Enums\CryptoNetwork;
use App\Enums\WalletCurrency;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreCapitalWithdrawalRequest;
use App\Http\Requests\User\StoreRewardWithdrawalRequest;
use App\Models\WithdrawalRequest;
use App\Services\MultiWalletService;
use App\Services\PlatformSettings;
use App\Services\UserWalletService;
use App\Services\WithdrawalOtpService;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawals,
        private readonly UserWalletService $wallet,
        private readonly MultiWalletService $multiWallet,
        private readonly WithdrawalOtpService $withdrawalOtp,
    ) {}

    public function index(Request $request): View
    {
        $user = auth()->user();
        $cryptoCodes = explode(',', WalletCurrency::cryptoCodes());
        $selected = strtoupper((string) $request->query('currency', 'USDT'));
        if (! in_array($selected, $cryptoCodes, true)) {
            $selected = 'USDT';
        }

        return view('user.withdraw.index', [
            'user' => $user,
            'stats' => $this->wallet->stats($user),
            'walletCards' => $this->multiWallet->walletCards($user),
            'selectedCurrency' => $selected,
            'selectedBalance' => $this->multiWallet->balance($user, WalletCurrency::normalize($selected)),
            'activePrincipal' => $user->activePrincipal(),
            'hasActiveDeposits' => $user->hasActiveDeposits(),
            'canWithdraw' => $this->wallet->canWithdraw($user),
            'kycApproved' => $user->isKycApproved(),
            'kycStatus' => $user->kyc_status,
            'nextWithdrawalAt' => $this->wallet->nextWithdrawalAt($user),
            'minRewardWithdrawal' => PlatformSettings::minRewardWithdrawal(),
            'withdrawalFeePercent' => PlatformSettings::withdrawalFeePercent(),
            'withdrawalFeeRate' => PlatformSettings::withdrawalFeeRate(),
            'withdrawals' => $user->withdrawalRequests()->latest()->paginate(10),
            'networks' => CryptoNetwork::cases(),
        ]);
    }

    public function sendOtp(Request $request): RedirectResponse|JsonResponse
    {
        try {
            $this->withdrawalOtp->send($request->user());
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => collect($e->errors())->flatten()->first()
                        ?: 'Could not send the verification email. Please try again in a moment.',
                ], 422);
            }

            throw $e;
        } catch (\Throwable $e) {
            report($e);

            $message = 'Could not send the verification email. Please try again in a moment.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 500);
            }

            return back()->withErrors([
                'email_otp' => $message,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Verification code sent successfully.',
            ]);
        }

        return back()->with('success', 'Verification code sent to '.$request->user()->email.'. Enter it below (valid for 10 minutes).');
    }

    public function storeReward(StoreRewardWithdrawalRequest $request): RedirectResponse
    {
        $this->withdrawals->requestReward(
            auth()->user(),
            (float) $request->validated('amount'),
            $request->validated('wallet_address'),
            $request->validated('network'),
            $request->validated('email_otp'),
            (string) $request->validated('currency'),
        );

        return redirect()
            ->route('user.withdraw.index')
            ->with('success', 'Reward withdrawal submitted successfully.');
    }

    public function storeCapital(StoreCapitalWithdrawalRequest $request): RedirectResponse
    {
        $this->withdrawals->requestCapital(
            auth()->user(),
            $request->validated('wallet_address'),
            $request->validated('network'),
            $request->validated('email_otp'),
        );

        return redirect()
            ->route('user.withdraw.index')
            ->with('success', 'Capital withdrawal requested. Processing within 72 hours.');
    }

    public function show(WithdrawalRequest $withdrawal): View
    {
        abort_unless($withdrawal->user_id === auth()->id(), 403);

        return view('user.withdraw.show', [
            'withdrawal' => $withdrawal,
        ]);
    }

    public function cancel(WithdrawalRequest $withdrawal): RedirectResponse
    {
        abort_unless($withdrawal->user_id === auth()->id(), 403);

        $this->withdrawals->cancel($withdrawal, auth()->user());

        return redirect()
            ->route('user.withdraw.index')
            ->with('success', 'Withdrawal request cancelled.');
    }
}
