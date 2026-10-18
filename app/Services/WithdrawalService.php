<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Enums\WithdrawalStatus;
use App\Enums\WithdrawalType;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Treasury;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawalService
{
    public function __construct(
        private readonly UserWalletService $wallet,
        private readonly MainWalletService $mainWallet,
        private readonly MultiWalletService $wallets,
        private readonly TreasuryStatsService $treasuryStats,
        private readonly NotificationService $notifications,
        private readonly WithdrawalOtpService $withdrawalOtp,
        private readonly DojahKycService $dojah,
    ) {}

    public function requestReward(User $user, float $amount, string $walletAddress, ?string $network = null, ?string $emailOtp = null, string $currency = 'USDT'): WithdrawalRequest
    {
        $this->assertKycApproved($user);
        $this->withdrawalOtp->assertValid($user, $emailOtp);
        $this->assertWithdrawalWindow($user);

        $walletCurrency = WalletCurrency::normalize($currency);
        if (! $walletCurrency->isCrypto()) {
            throw ValidationException::withMessages([
                'currency' => 'Crypto withdrawal supports BTC, USDT, or TRX.',
            ]);
        }

        $min = PlatformSettings::minRewardWithdrawal();

        if ($walletCurrency === WalletCurrency::Usdt && $amount < $min) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum withdrawal is '.number_format($min, 2).' USDT.',
            ]);
        }

        if ($amount > $this->wallets->balance($user, $walletCurrency)) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient '.$walletCurrency->value.' wallet balance.',
            ]);
        }

        if ($user->withdrawalRequests()
            ->where('type', WithdrawalType::Reward)
            ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved])
            ->exists()) {
            throw ValidationException::withMessages([
                'amount' => 'You already have a pending withdrawal.',
            ]);
        }

        $this->dojah->assertWithdrawalAllowed(
            $user,
            $amount,
            $walletCurrency->value,
            'wd-reward-'.$user->id.'-'.now()->timestamp,
        );

        return DB::transaction(function () use ($user, $amount, $walletAddress, $network, $walletCurrency) {
            $fee = $walletCurrency === WalletCurrency::Usdt
                ? PlatformSettings::calculateWithdrawalFee($amount)
                : round($amount * (PlatformSettings::withdrawalFeePercent() / 100), 8);
            $netAmount = $amount - $fee;

            $request = WithdrawalRequest::query()->create([
                'user_id' => $user->id,
                'type' => WithdrawalType::Reward,
                'currency' => $walletCurrency->value,
                'amount' => $amount,
                'fee' => $fee,
                'wallet_address' => $walletAddress,
                'network' => $network,
                'status' => WithdrawalStatus::Pending,
            ]);

            $this->wallets->debit(
                $user,
                $walletCurrency,
                $amount,
                TransactionType::CryptoWithdrawal,
                $walletCurrency->value.' Withdrawal (Pending — fee '.$walletCurrency->format($fee).')',
                $request,
            );

            $this->notifications->notifyUser(
                $user,
                'withdrawal',
                'Withdrawal Submitted',
                'Your '.$walletCurrency->value.' withdrawal of '.$walletCurrency->format($amount).' has been submitted. You will receive '.$walletCurrency->format($netAmount).' after fee.',
            );

            $this->withdrawalOtp->consume($user);

            return $request->fresh();
        });
    }

    public function requestCapital(User $user, string $walletAddress, ?string $network = null, ?string $emailOtp = null): WithdrawalRequest
    {
        $this->assertKycApproved($user);
        $this->withdrawalOtp->assertValid($user, $emailOtp);
        $this->assertWithdrawalWindow($user);

        $deposit = $user->activeDeposit();
        $amount = $user->activePrincipal();

        if (! $deposit || $amount <= 0) {
            throw ValidationException::withMessages([
                'wallet_address' => 'No active investment to withdraw.',
            ]);
        }

        if ($user->withdrawalRequests()
            ->where('type', WithdrawalType::Capital)
            ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved])
            ->exists()) {
            throw ValidationException::withMessages([
                'wallet_address' => 'You already have a pending capital withdrawal.',
            ]);
        }

        $this->dojah->assertWithdrawalAllowed(
            $user,
            $amount,
            'USDT',
            'wd-capital-'.$user->id.'-'.now()->timestamp,
        );

        $fee = 0.0;
        $netAmount = $amount;

        return DB::transaction(function () use ($user, $deposit, $amount, $fee, $netAmount, $walletAddress, $network) {
            $request = WithdrawalRequest::query()->create([
                'user_id' => $user->id,
                'deposit_id' => $deposit->id,
                'type' => WithdrawalType::Capital,
                'amount' => $amount,
                'fee' => $fee,
                'wallet_address' => $walletAddress,
                'network' => $network,
                'status' => WithdrawalStatus::Pending,
            ]);

            Transaction::query()->create([
                'user_id' => $user->id,
                'type' => TransactionType::CapitalWithdrawal,
                'amount' => $amount,
                'reference_type' => WithdrawalRequest::class,
                'reference_id' => $request->id,
                'description' => 'Capital Withdrawal (Pending — no Platform Fee on principal)',
                'status' => 'pending',
            ]);

            $this->notifications->notifyUser(
                $user,
                'withdrawal',
                'Capital Withdrawal Requested',
                'Your capital withdrawal of '.number_format($amount, 2).' USDT will be processed within 72 hours. Principal withdrawals are not subject to the Platform Fee.',
            );

            $this->withdrawalOtp->consume($user);

            return $request->fresh();
        });
    }

    public function complete(WithdrawalRequest $request, ?User $admin): WithdrawalRequest
    {
        if (! in_array($request->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true)) {
            throw ValidationException::withMessages([
                'withdrawal' => 'This withdrawal cannot be completed.',
            ]);
        }

        return DB::transaction(function () use ($request, $admin) {
            $request->update([
                'status' => WithdrawalStatus::Completed,
                'processed_at' => now(),
                'processed_by' => $admin?->id,
            ]);

            $txType = $request->type === WithdrawalType::Capital
                ? TransactionType::CapitalWithdrawal
                : TransactionType::RewardWithdrawal;

            $request->user->transactions()
                ->where('reference_type', WithdrawalRequest::class)
                ->where('reference_id', $request->id)
                ->update([
                    'status' => 'completed',
                    'description' => $request->type === WithdrawalType::Capital
                        ? 'Capital Withdrawal'
                        : 'Reward Withdrawal',
                ]);

            $treasury = Treasury::query()->first();

            if ($treasury) {
                $treasury->decrement('balance', $request->netAmount());
            }

            if ($request->type === WithdrawalType::Capital) {
                Deposit::query()
                    ->where('user_id', $request->user_id)
                    ->where('status', DepositStatus::Active)
                    ->update(['status' => DepositStatus::Withdrawn]);
            }

            if ($request->type === WithdrawalType::Capital) {
                $this->notifications->notifyUser(
                    $request->user,
                    'withdrawal',
                    'Capital Withdrawal Completed',
                    'Your capital withdrawal has been sent. You received '.number_format($request->netAmount(), 2).' USDT (no Platform Fee on principal).',
                );
            } else {
                $this->notifications->notifyUser(
                    $request->user,
                    'withdrawal',
                    'Reward Withdrawal Successful',
                    'Your reward withdrawal has been processed. You received '.number_format($request->netAmount(), 2).' USDT after the '.PlatformSettings::withdrawalFeePercent().'% fee.',
                );
            }

            return $request->fresh();
        });
    }

    public function approve(WithdrawalRequest $request, ?User $admin): WithdrawalRequest
    {
        if ($request->type !== WithdrawalType::Capital) {
            throw ValidationException::withMessages([
                'withdrawal' => 'Only capital withdrawals require approval.',
            ]);
        }

        if ($request->status !== WithdrawalStatus::Pending) {
            throw ValidationException::withMessages([
                'withdrawal' => 'This withdrawal cannot be approved.',
            ]);
        }

        $request->update(['status' => WithdrawalStatus::Approved]);

        $this->notifications->notifyUser(
            $request->user,
            'withdrawal',
            'Capital Withdrawal Approved',
            'Your capital withdrawal of '.number_format((float) $request->amount, 2).' USDT has been approved. Principal withdrawals have no Platform Fee. You will receive '.number_format($request->netAmount(), 2).' USDT within 72 hours.',
        );

        return $request->fresh();
    }

    public function reject(WithdrawalRequest $request, ?User $admin, ?string $notes = null): WithdrawalRequest
    {
        if (! in_array($request->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true)) {
            throw ValidationException::withMessages([
                'withdrawal' => 'This withdrawal cannot be rejected.',
            ]);
        }

        return DB::transaction(function () use ($request, $admin, $notes) {
            $request->update([
                'status' => WithdrawalStatus::Rejected,
                'processed_at' => now(),
                'processed_by' => $admin?->id,
                'notes' => $notes,
            ]);

            $request->user->transactions()
                ->where('reference_type', WithdrawalRequest::class)
                ->where('reference_id', $request->id)
                ->update(['status' => 'rejected']);

            if ($request->type === WithdrawalType::Reward) {
                $currency = WalletCurrency::tryNormalize($request->currency) ?? WalletCurrency::Usdt;
                $this->wallets->credit(
                    $request->user,
                    $currency,
                    (float) $request->amount,
                    TransactionType::MainWalletCredit,
                    'Withdrawal rejected — refund to '.$currency->value.' wallet',
                    $request,
                );
            }

            $this->notifications->notifyUser(
                $request->user,
                'withdrawal',
                'Withdrawal Rejected',
                $notes ?: 'Your withdrawal request could not be processed. Please contact support.',
            );

            return $request->fresh();
        });
    }

    public function cancel(WithdrawalRequest $request, User $user): WithdrawalRequest
    {
        if ($request->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'withdrawal' => 'You are not allowed to cancel this withdrawal.',
            ]);
        }

        if ($request->status !== WithdrawalStatus::Pending) {
            throw ValidationException::withMessages([
                'withdrawal' => 'Only pending withdrawals can be cancelled.',
            ]);
        }

        return DB::transaction(function () use ($request) {
            $request->update([
                'status' => WithdrawalStatus::Cancelled,
                'processed_at' => now(),
            ]);

            $request->user->transactions()
                ->where('reference_type', WithdrawalRequest::class)
                ->where('reference_id', $request->id)
                ->update([
                    'status' => 'cancelled',
                    'description' => $request->type === WithdrawalType::Capital
                        ? 'Capital Withdrawal (Cancelled)'
                        : 'Main Wallet Withdrawal (Cancelled)',
                ]);

            if ($request->type === WithdrawalType::Reward) {
                $currency = WalletCurrency::tryNormalize($request->currency) ?? WalletCurrency::Usdt;
                $this->wallets->credit(
                    $request->user,
                    $currency,
                    (float) $request->amount,
                    TransactionType::MainWalletCredit,
                    'Withdrawal cancelled — refund to '.$currency->value.' wallet',
                    $request,
                );
            }

            return $request->fresh();
        });
    }

    private function assertKycApproved(User $user): void
    {
        if ($user->isKycApproved()) {
            return;
        }

        throw ValidationException::withMessages([
            'kyc' => 'Your KYC is still under verification. Withdrawal requests can only be submitted after an admin confirms your identity. Your withdrawal will be available once KYC is confirmed.',
        ]);
    }

    private function assertWithdrawalWindow(User $user): void
    {
        if (! $this->wallet->canWithdraw($user)) {
            $next = $this->wallet->nextWithdrawalAt($user);
            $message = $next
                ? 'Withdrawals are available after '.$next->format('M d, Y h:i A').'.'
                : 'Withdrawals are temporarily unavailable.';

            throw ValidationException::withMessages(['amount' => $message]);
        }
    }
}
