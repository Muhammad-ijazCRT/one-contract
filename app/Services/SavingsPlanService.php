<?php

namespace App\Services;

use App\Enums\RewardType;
use App\Enums\SavingsDeductionStatus;
use App\Enums\SavingsPlanStatus;
use App\Enums\TermsDocument;
use App\Enums\TransactionType;
use App\Models\Reward;
use App\Models\SavingsDeduction;
use App\Models\SavingsPlan;
use App\Models\TermsAcceptance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavingsPlanService
{
    public function __construct(
        private readonly MainWalletService $mainWallet,
        private readonly NotificationService $notifications,
    ) {}

    public function enroll(User $user, float $dailyAmount, string $name, ?string $ip = null, ?string $userAgent = null): SavingsPlan
    {
        $this->assertTermsAccepted($user);

        $min = PlatformSettings::savingsMinDaily();
        $max = PlatformSettings::savingsMaxDaily();

        if ($dailyAmount < $min || $dailyAmount > $max) {
            throw ValidationException::withMessages([
                'amount' => 'Daily savings amount must be between '.number_format($min, 2).' and '.number_format($max, 2).' USDT.',
            ]);
        }

        $activePlansCount = $user->savingsPlans()->where('status', SavingsPlanStatus::Active)->count();
        if ($activePlansCount >= 100) {
            throw ValidationException::withMessages([
                'amount' => 'You can only have up to 100 active Savings Plans.',
            ]);
        }

        $targetDays = PlatformSettings::savingsTargetDays();

        $plan = DB::transaction(function () use ($user, $dailyAmount, $name, $targetDays) {
            $plan = SavingsPlan::query()->create([
                'user_id' => $user->id,
                'name' => $name,
                'principal' => 0,
                'daily_amount' => $dailyAmount,
                'target_days' => $targetDays,
                'successful_days' => 0,
                'total_saved' => 0,
                'accrued_interest' => 0,
                'status' => SavingsPlanStatus::Active,
                'started_at' => now(),
                'estimated_completion_at' => now()->addDays($targetDays),
            ]);

            $this->notifications->notifyUser(
                $user,
                'savings',
                'Savings Plan Started',
                number_format($dailyAmount, 2).' USDT will be deducted daily from Main Wallet until '
                    .$targetDays.' successful days. Reward on completion: '
                    .PlatformSettings::savingsRewardPercent().'% of total saved.',
            );

            return $plan;
        });

        // Attempt day-1 deduction immediately when balance allows.
        $this->processPlanDay($plan->fresh(['user']), now()->startOfDay());
        $plan->refresh()->syncProgressFromDeductions();

        return $plan->fresh();
    }

    public function requestEarlyWithdrawal(User $user, SavingsPlan $plan): SavingsPlan
    {
        if ($plan->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'plan' => 'Invalid savings plan.',
            ]);
        }

        if ($plan->status !== SavingsPlanStatus::Active) {
            throw ValidationException::withMessages([
                'plan' => 'Only an active Savings Plan can be withdrawn early.',
            ]);
        }

        $saved = (float) $plan->total_saved;

        if ($saved <= 0) {
            // Nothing saved yet — close immediately with no return queue
            $plan->update([
                'status' => SavingsPlanStatus::WithdrawnEarly,
                'accrued_interest' => 0,
                'completed_at' => now(),
                'early_withdrawal_requested_at' => now(),
            ]);

            $this->notifications->notifyUser(
                $user,
                'savings',
                'Savings Plan Ended',
                'Early withdrawal completed. No funds were saved yet, so nothing will be returned.',
            );

            return $plan->fresh();
        }

        $hours = PlatformSettings::savingsEarlyReturnHours();

        $plan->update([
            'status' => SavingsPlanStatus::PendingEarlyReturn,
            'accrued_interest' => 0, // reward cancelled
            'early_withdrawal_requested_at' => now(),
            'early_withdrawal_available_at' => now()->addHours($hours),
        ]);

        $this->notifications->notifyUser(
            $user,
            'savings',
            'Early Withdrawal Requested',
            number_format($saved, 2).' USDT successfully saved will return within '.$hours
                .' hours. Completion reward was cancelled.',
        );

        return $plan->fresh();
    }

    public function processDailyDeductions(?Carbon $forDate = null): int
    {
        $processed = 0;

        $plans = SavingsPlan::query()
            ->with('user')
            ->where('status', SavingsPlanStatus::Active)
            ->get();

        foreach ($plans as $plan) {
            if ($forDate !== null) {
                if ($this->processPlanDay($plan, $forDate->copy()->startOfDay())) {
                    $processed++;
                }
            } else {
                $processed += $this->catchUpPlan($plan);
            }

            $plan->refresh();
            if ($plan->status === SavingsPlanStatus::Active) {
                $plan->syncProgressFromDeductions();
            }
        }

        return $processed;
    }

    /**
     * Process every calendar day from plan start through today (idempotent).
     * Fixes missed cron days so successful_days / total_saved catch up when balance allows.
     */
    public function catchUpPlan(SavingsPlan $plan): int
    {
        if ($plan->status !== SavingsPlanStatus::Active) {
            return 0;
        }

        $start = ($plan->started_at ?? $plan->created_at ?? now())->copy()->startOfDay();
        $end = now()->copy()->startOfDay();
        $processed = 0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $plan->refresh();
            if ($plan->status !== SavingsPlanStatus::Active) {
                break;
            }

            if ($this->processPlanDay($plan, $day->copy())) {
                $processed++;
            }
        }

        return $processed;
    }

    /** Catch up active plans for one user (e.g. when they open Savings page). */
    public function catchUpUser(User $user): int
    {
        $processed = 0;

        $plans = SavingsPlan::query()
            ->with('user')
            ->where('user_id', $user->id)
            ->where('status', SavingsPlanStatus::Active)
            ->get();

        foreach ($plans as $plan) {
            $processed += $this->catchUpPlan($plan);
            $plan->refresh();
            if ($plan->status === SavingsPlanStatus::Active) {
                $plan->syncProgressFromDeductions();
            }
        }

        return $processed;
    }

    /** @deprecated Kept for schedule compatibility — daily interest model replaced by deductions */
    public function processDailyInterest(?Carbon $forDate = null): int
    {
        return $this->processDailyDeductions($forDate);
    }

    public function processPlanDay(SavingsPlan $plan, Carbon $date): bool
    {
        if ($plan->status !== SavingsPlanStatus::Active) {
            return false;
        }

        $dateString = $date->toDateString();

        $existing = SavingsDeduction::query()
            ->where('savings_plan_id', $plan->id)
            ->whereDate('deduction_date', $dateString)
            ->first();

        // Already succeeded for this calendar day.
        if ($existing && $existing->status === SavingsDeductionStatus::Success) {
            return false;
        }

        return DB::transaction(function () use ($plan, $dateString) {
            $locked = SavingsPlan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== SavingsPlanStatus::Active) {
                return false;
            }

            $row = SavingsDeduction::query()
                ->where('savings_plan_id', $locked->id)
                ->whereDate('deduction_date', $dateString)
                ->lockForUpdate()
                ->first();

            if ($row && $row->status === SavingsDeductionStatus::Success) {
                return false;
            }

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();
            $amount = round((float) $locked->daily_amount, 8);

            if ($amount <= 0) {
                return false;
            }

            $balance = $this->mainWallet->balance($user);

            if ($balance < $amount) {
                if (! $row) {
                    SavingsDeduction::query()->create([
                        'savings_plan_id' => $locked->id,
                        'user_id' => $user->id,
                        'amount' => $amount,
                        'status' => SavingsDeductionStatus::Skipped,
                        'deduction_date' => $dateString,
                        'processed_at' => now(),
                    ]);

                    $this->notifications->notifyUser(
                        $user,
                        'savings',
                        'Savings Day Skipped',
                        'Insufficient Main Wallet balance to save '
                            .number_format($amount, 2).' USDT on '.$dateString
                            .'. Day not counted — top up and keep your plan active.',
                        ['url' => route('user.savings.index')],
                    );
                }

                // Day skipped — does not count; plan stays active
                return (bool) (! $row);
            }

            $nextDay = (int) $locked->successful_days + 1;
            $targetDays = (int) $locked->target_days;

            $this->mainWallet->debit(
                $user,
                $amount,
                TransactionType::SavingsDeduction,
                'Daily savings deduction ('.$nextDay.'/'.$targetDays.')',
                $locked,
            );

            if ($row && $row->status === SavingsDeductionStatus::Skipped) {
                $row->update([
                    'status' => SavingsDeductionStatus::Success,
                    'amount' => $amount,
                    'processed_at' => now(),
                ]);
            } else {
                SavingsDeduction::query()->create([
                    'savings_plan_id' => $locked->id,
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'status' => SavingsDeductionStatus::Success,
                    'deduction_date' => $dateString,
                    'processed_at' => now(),
                ]);
            }

            $locked->total_saved = (float) $locked->total_saved + $amount;
            $locked->successful_days = $nextDay;
            $locked->last_interest_date = $dateString;
            $remaining = max(0, $targetDays - (int) $locked->successful_days);
            $locked->estimated_completion_at = $remaining > 0 ? now()->addDays($remaining) : now();
            $locked->save();

            $progress = $targetDays > 0
                ? round(((int) $locked->successful_days / $targetDays) * 100, 1)
                : 0;

            $this->notifications->notifyUser(
                $user,
                'savings',
                'Daily Savings Deducted',
                number_format($amount, 2).' USDT moved from Main Wallet to savings ('
                    .(int) $locked->successful_days.'/'.$targetDays.' days). Total saved $'
                    .number_format((float) $locked->total_saved, 2)
                    .' · Progress '.$progress.'%.',
                ['url' => route('user.savings.index')],
            );

            if ((int) $locked->successful_days >= $targetDays) {
                $this->markMatured($locked->fresh());
            }

            return true;
        });
    }

    public function markMatured(SavingsPlan $plan): SavingsPlan
    {
        if ($plan->status !== SavingsPlanStatus::Active) {
            return $plan;
        }

        return DB::transaction(function () use ($plan) {
            $locked = SavingsPlan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== SavingsPlanStatus::Active) {
                return $locked;
            }

            $totalSaved = round((float) $locked->total_saved, 8);
            $rewardPercent = PlatformSettings::savingsRewardPercent();
            $rewardAmount = round($totalSaved * ($rewardPercent / 100), 8);
            $hours = PlatformSettings::savingsEarlyReturnHours();

            if ($rewardAmount > 0) {
                Reward::query()->create([
                    'user_id' => $locked->user_id,
                    'type' => RewardType::SavingsInterest,
                    'amount' => $rewardAmount,
                    'credited_at' => now(),
                    'claimed_at' => null,
                ]);
            }

            // Capital returns automatically within 72h — reward stays claimable via Claim Rewards.
            $locked->update([
                'status' => SavingsPlanStatus::PendingMaturityReturn,
                'matured_at' => now(),
                'accrued_interest' => 0,
                'early_withdrawal_available_at' => now()->addHours($hours),
            ]);

            $this->notifications->notifyUser(
                $locked->user,
                'savings',
                'Savings Plan Completed',
                'You completed '.$locked->successful_days.' successful days. Reward of '
                    .number_format($rewardAmount, 2).' USDT ('.$rewardPercent.'% of '
                    .number_format($totalSaved, 2).' saved) is ready in Claim Rewards. '
                    .'Saved capital returns to Main Wallet within '.$hours.' hours (no Platform Fee).',
            );

            return $locked->fresh();
        });
    }

    /** @deprecated Use markMatured — kept for schedule/command compatibility */
    public function completePlan(SavingsPlan $plan): SavingsPlan
    {
        return $this->markMatured($plan);
    }

    public function returnPrincipal(User $user, SavingsPlan $plan): SavingsPlan
    {
        if ($plan->user_id !== $user->id) {
            throw ValidationException::withMessages(['plan' => 'Invalid savings plan.']);
        }

        // Manual return kept for already-matured legacy plans; new flow auto-returns.
        if (! in_array($plan->status, [SavingsPlanStatus::Matured, SavingsPlanStatus::PendingMaturityReturn], true)) {
            throw ValidationException::withMessages(['plan' => 'Saved funds can only be returned after completion.']);
        }

        return DB::transaction(function () use ($plan) {
            $locked = SavingsPlan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [SavingsPlanStatus::Matured, SavingsPlanStatus::PendingMaturityReturn], true)) {
                throw ValidationException::withMessages(['plan' => 'Saved funds can only be returned after completion.']);
            }

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();
            $saved = (float) $locked->total_saved;

            if ($saved > 0) {
                $this->mainWallet->credit(
                    $user,
                    $saved,
                    TransactionType::SavingsPrincipalReturn,
                    'Savings returned to Main Wallet (no Platform Fee)',
                    $locked,
                );
            }

            $locked->update([
                'status' => SavingsPlanStatus::Completed,
                'completed_at' => now(),
                'principal' => 0,
                'total_saved' => 0,
            ]);

            return $locked->fresh();
        });
    }

    public function completeReadyEarlyReturns(?int $limit = null): int
    {
        $query = SavingsPlan::query()
            ->with('user')
            ->where('status', SavingsPlanStatus::PendingEarlyReturn)
            ->whereNotNull('early_withdrawal_available_at')
            ->where('early_withdrawal_available_at', '<=', now())
            ->orderBy('early_withdrawal_available_at');

        if ($limit) {
            $query->limit($limit);
        }

        $completed = 0;

        foreach ($query->get() as $plan) {
            $this->completeEarlyReturn($plan);
            $completed++;
        }

        return $completed;
    }

    public function completeReadyMaturityReturns(?int $limit = null): int
    {
        $query = SavingsPlan::query()
            ->with('user')
            ->whereIn('status', [SavingsPlanStatus::PendingMaturityReturn, SavingsPlanStatus::Matured])
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('status', SavingsPlanStatus::PendingMaturityReturn)
                        ->whereNotNull('early_withdrawal_available_at')
                        ->where('early_withdrawal_available_at', '<=', now());
                })->orWhere(function ($inner) {
                    // Legacy Matured plans: return after matured_at + wait hours
                    $inner->where('status', SavingsPlanStatus::Matured)
                        ->whereNotNull('matured_at')
                        ->where('matured_at', '<=', now()->subHours(PlatformSettings::savingsEarlyReturnHours()));
                });
            })
            ->orderBy('id');

        if ($limit) {
            $query->limit($limit);
        }

        $completed = 0;

        foreach ($query->get() as $plan) {
            $this->completeMaturityReturn($plan);
            $completed++;
        }

        return $completed;
    }

    public function completeMaturityReturn(SavingsPlan $plan): SavingsPlan
    {
        if (! in_array($plan->status, [SavingsPlanStatus::PendingMaturityReturn, SavingsPlanStatus::Matured], true)) {
            return $plan;
        }

        return DB::transaction(function () use ($plan) {
            $locked = SavingsPlan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [SavingsPlanStatus::PendingMaturityReturn, SavingsPlanStatus::Matured], true)) {
                return $locked;
            }

            $amount = (float) $locked->total_saved;
            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();

            if ($amount > 0) {
                $this->mainWallet->credit(
                    $user,
                    $amount,
                    TransactionType::SavingsPrincipalReturn,
                    'Savings maturity capital returned to Main Wallet (no Platform Fee)',
                    $locked,
                );
            }

            $locked->update([
                'status' => SavingsPlanStatus::Completed,
                'completed_at' => now(),
                'principal' => 0,
                'total_saved' => 0,
            ]);

            $this->notifications->notifyUser(
                $user,
                'savings',
                'Savings Capital Returned',
                number_format($amount, 2).' USDT returned to Main Wallet. Claim your completion reward from Claim Rewards.',
            );

            return $locked->fresh();
        });
    }

    public function completeEarlyReturn(SavingsPlan $plan): SavingsPlan
    {
        if ($plan->status !== SavingsPlanStatus::PendingEarlyReturn) {
            return $plan;
        }

        return DB::transaction(function () use ($plan) {
            $locked = SavingsPlan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== SavingsPlanStatus::PendingEarlyReturn) {
                return $locked;
            }

            $amount = (float) $locked->total_saved;
            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();

            if ($amount > 0) {
                $this->mainWallet->credit(
                    $user,
                    $amount,
                    TransactionType::SavingsEarlyWithdrawal,
                    'Early savings return (reward cancelled)',
                    $locked,
                );
            }

            $locked->update([
                'status' => SavingsPlanStatus::WithdrawnEarly,
                'completed_at' => now(),
                'principal' => 0,
                'total_saved' => 0,
                'accrued_interest' => 0,
            ]);

            $this->notifications->notifyUser(
                $user,
                'savings',
                'Early Savings Returned',
                number_format($amount, 2).' USDT returned to Main Wallet. Completion reward was cancelled.',
            );

            return $locked->fresh();
        });
    }

    public function acceptTerms(User $user, ?string $ip = null, ?string $userAgent = null): TermsAcceptance
    {
        return TermsAcceptance::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'document_key' => TermsDocument::SavingsPlan->value,
            ],
            [
                'accepted_at' => now(),
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]
        );
    }

    private function assertTermsAccepted(User $user): void
    {
        if (! $user->hasAcceptedTerms(TermsDocument::SavingsPlan)) {
            throw ValidationException::withMessages([
                'terms' => 'Please accept the Savings Plan Terms & Conditions first.',
            ]);
        }
    }
}
