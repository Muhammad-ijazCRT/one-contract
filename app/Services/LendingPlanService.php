<?php

namespace App\Services;

use App\Enums\LendingPlanStatus;
use App\Enums\LendingPlanType;
use App\Enums\RewardType;
use App\Enums\TransactionType;
use App\Models\LendingPlan;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LendingPlanService
{
    public function __construct(
        private readonly MainWalletService $mainWallet,
        private readonly NotificationService $notifications,
    ) {}

    public function open(User $user, LendingPlanType $type, float $amount): LendingPlan
    {
        [$min, $max, $rate, $days] = $this->specsFor($type);

        if ($amount < $min || $amount > $max) {
            throw ValidationException::withMessages([
                'amount' => $type->label().' amount must be between '.number_format($min, 2).' and '.number_format($max, 2).' USDT.',
            ]);
        }

        if ($amount > $this->mainWallet->balance($user)) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient Main Wallet balance.',
            ]);
        }

        $openExists = LendingPlan::query()
            ->where('user_id', $user->id)
            ->where('type', $type)
            ->whereIn('status', [
                LendingPlanStatus::Active,
                LendingPlanStatus::PendingEarlyReturn,
                LendingPlanStatus::PendingMaturityReturn,
                LendingPlanStatus::Matured,
            ])
            ->exists();

        if ($openExists) {
            throw ValidationException::withMessages([
                'amount' => 'You can only have one open '.$type->label().' position at a time.',
            ]);
        }

        return DB::transaction(function () use ($user, $type, $amount, $rate, $days) {
            $plan = LendingPlan::query()->create([
                'user_id' => $user->id,
                'type' => $type,
                'principal' => $amount,
                'daily_rate_percent' => $rate,
                'duration_days' => $days,
                'accrued_interest' => 0,
                'status' => LendingPlanStatus::Active,
                'started_at' => now(),
                'matures_at' => now()->addDays($days),
            ]);

            $this->mainWallet->debit(
                $user,
                $amount,
                TransactionType::LendingPrincipalLock,
                $type->label().' principal locked',
                $plan,
            );

            $this->notifications->notifyUser(
                $user,
                'lending',
                $type->label().' Started',
                number_format($amount, 2).' USDT locked for '.$days.' days at '.$rate.'% flat reward.',
            );

            return $plan->fresh();
        });
    }

    public function processDailyInterest(?Carbon $forDate = null): int
    {
        $date = ($forDate ?? now())->copy()->endOfDay();
        $processed = 0;

        // Flat-rate lending: no daily accrual — only mature plans that reached matures_at
        $plans = LendingPlan::query()
            ->with('user')
            ->where('status', LendingPlanStatus::Active)
            ->whereNotNull('matures_at')
            ->where('matures_at', '<=', $date)
            ->get();

        foreach ($plans as $plan) {
            $this->markMatured($plan);
            $processed++;
        }

        return $processed;
    }

    public function accrueDay(LendingPlan $plan, Carbon $date): bool
    {
        // Flat reward model — interest is calculated once at maturity
        if (! $plan->isActive()) {
            return false;
        }

        if ($plan->matures_at && $plan->matures_at->lte($date->copy()->endOfDay())) {
            $this->markMatured($plan);

            return true;
        }

        return false;
    }

    public function markMatured(LendingPlan $plan): LendingPlan
    {
        if ($plan->status !== LendingPlanStatus::Active) {
            return $plan;
        }

        return DB::transaction(function () use ($plan) {
            $locked = LendingPlan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== LendingPlanStatus::Active) {
                return $locked;
            }

            // Flat rate reward: principal × rate%
            $interest = round((float) $locked->principal * ((float) $locked->daily_rate_percent / 100), 8);
            $hours = PlatformSettings::lendingEarlyReturnHours();

            if ($interest > 0) {
                Reward::query()->create([
                    'user_id' => $locked->user_id,
                    'type' => RewardType::LendingInterest,
                    'amount' => $interest,
                    'credited_at' => now(),
                    'claimed_at' => null,
                ]);
            }

            $locked->update([
                'status' => LendingPlanStatus::PendingMaturityReturn,
                'matured_at' => now(),
                'accrued_interest' => 0,
                'last_interest_date' => now()->toDateString(),
                'early_withdrawal_available_at' => now()->addHours($hours),
            ]);

            $this->notifications->notifyUser(
                $locked->user,
                'lending',
                $locked->type->label().' Matured',
                'Flat reward of '.number_format($interest, 2).' USDT ('
                    .number_format((float) $locked->daily_rate_percent, 2).'%) is ready in Claim Rewards. '
                    .'Principal returns to Main Wallet within '.$hours.' hours (no Platform Fee).',
            );

            return $locked->fresh();
        });
    }

    public function returnPrincipal(User $user, LendingPlan $plan): LendingPlan
    {
        if ($plan->user_id !== $user->id) {
            throw ValidationException::withMessages(['plan' => 'Invalid lending plan.']);
        }

        if (! in_array($plan->status, [LendingPlanStatus::Matured, LendingPlanStatus::PendingMaturityReturn], true)) {
            throw ValidationException::withMessages(['plan' => 'Principal can only be returned after maturity.']);
        }

        return DB::transaction(function () use ($plan) {
            $locked = LendingPlan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [LendingPlanStatus::Matured, LendingPlanStatus::PendingMaturityReturn], true)) {
                throw ValidationException::withMessages(['plan' => 'Principal can only be returned after maturity.']);
            }

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();
            $principal = (float) $locked->principal;

            $this->mainWallet->credit(
                $user,
                $principal,
                TransactionType::LendingPrincipalReturn,
                $locked->type->label().' principal returned (no Platform Fee)',
                $locked,
            );

            $locked->update([
                'status' => LendingPlanStatus::Completed,
                'completed_at' => now(),
                'principal' => 0,
            ]);

            return $locked->fresh();
        });
    }

    public function requestEarlyWithdrawal(User $user, LendingPlan $plan): LendingPlan
    {
        if ($plan->user_id !== $user->id) {
            throw ValidationException::withMessages(['plan' => 'Invalid lending plan.']);
        }

        if ($plan->status !== LendingPlanStatus::Active) {
            throw ValidationException::withMessages(['plan' => 'Only an active lending plan can be cancelled early.']);
        }

        $hours = PlatformSettings::lendingEarlyReturnHours();

        $plan->update([
            'status' => LendingPlanStatus::PendingEarlyReturn,
            'accrued_interest' => 0, // early cancel forfeits interest
            'early_withdrawal_requested_at' => now(),
            'early_withdrawal_available_at' => now()->addHours($hours),
        ]);

        $this->notifications->notifyUser(
            $user,
            'lending',
            'Early Lending Cancellation',
            'Accrued interest was cancelled. Eligible principal returns within '.$hours.' hours.',
        );

        return $plan->fresh();
    }

    public function completeReadyEarlyReturns(?int $limit = null): int
    {
        $query = LendingPlan::query()
            ->where('status', LendingPlanStatus::PendingEarlyReturn)
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
        $query = LendingPlan::query()
            ->whereIn('status', [LendingPlanStatus::PendingMaturityReturn, LendingPlanStatus::Matured])
            ->where(function ($q) {
                $q->where(function ($inner) {
                    $inner->where('status', LendingPlanStatus::PendingMaturityReturn)
                        ->whereNotNull('early_withdrawal_available_at')
                        ->where('early_withdrawal_available_at', '<=', now());
                })->orWhere(function ($inner) {
                    $inner->where('status', LendingPlanStatus::Matured)
                        ->whereNotNull('matured_at')
                        ->where('matured_at', '<=', now()->subHours(PlatformSettings::lendingEarlyReturnHours()));
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

    public function completeMaturityReturn(LendingPlan $plan): LendingPlan
    {
        if (! in_array($plan->status, [LendingPlanStatus::PendingMaturityReturn, LendingPlanStatus::Matured], true)) {
            return $plan;
        }

        return DB::transaction(function () use ($plan) {
            $locked = LendingPlan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [LendingPlanStatus::PendingMaturityReturn, LendingPlanStatus::Matured], true)) {
                return $locked;
            }

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();
            $principal = (float) $locked->principal;

            if ($principal > 0) {
                $this->mainWallet->credit(
                    $user,
                    $principal,
                    TransactionType::LendingPrincipalReturn,
                    $locked->type->label().' maturity principal returned (no Platform Fee)',
                    $locked,
                );
            }

            $locked->update([
                'status' => LendingPlanStatus::Completed,
                'completed_at' => now(),
                'principal' => 0,
            ]);

            $this->notifications->notifyUser(
                $user,
                'lending',
                $locked->type->label().' Principal Returned',
                number_format($principal, 2).' USDT returned to Main Wallet. Claim interest from Claim Rewards.',
            );

            return $locked->fresh();
        });
    }

    public function completeEarlyReturn(LendingPlan $plan): LendingPlan
    {
        if ($plan->status !== LendingPlanStatus::PendingEarlyReturn) {
            return $plan;
        }

        return DB::transaction(function () use ($plan) {
            $locked = LendingPlan::query()->whereKey($plan->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== LendingPlanStatus::PendingEarlyReturn) {
                return $locked;
            }

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();
            $principal = (float) $locked->principal;

            if ($principal > 0) {
                $this->mainWallet->credit(
                    $user,
                    $principal,
                    TransactionType::LendingPrincipalReturn,
                    $locked->type->label().' early principal return (interest forfeited)',
                    $locked,
                );
            }

            $locked->update([
                'status' => LendingPlanStatus::WithdrawnEarly,
                'completed_at' => now(),
                'principal' => 0,
                'accrued_interest' => 0,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * @return array{0: float, 1: float, 2: float, 3: int}
     */
    private function specsFor(LendingPlanType $type): array
    {
        return match ($type) {
            LendingPlanType::Regular => [
                PlatformSettings::regularLendingMin(),
                PlatformSettings::regularLendingMax(),
                PlatformSettings::regularLendingDailyRatePercent(),
                PlatformSettings::regularLendingDurationDays(),
            ],
            LendingPlanType::Premium => [
                PlatformSettings::premiumLendingMin(),
                PlatformSettings::premiumLendingMax(),
                PlatformSettings::premiumLendingDailyRatePercent(),
                PlatformSettings::premiumLendingDurationDays(),
            ],
        };
    }
}
