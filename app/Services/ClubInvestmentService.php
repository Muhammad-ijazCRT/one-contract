<?php

namespace App\Services;

use App\Enums\ClubCapitalRequestStatus;
use App\Enums\ClubInvestmentStatus;
use App\Enums\TermsDocument;
use App\Enums\TransactionType;
use App\Models\ClubCapitalRequest;
use App\Models\ClubInvestment;
use App\Models\InvestmentClub;
use App\Models\TermsAcceptance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClubInvestmentService
{
    public function __construct(
        private readonly MainWalletService $mainWallet,
        private readonly NotificationService $notifications,
    ) {}

    public function invest(User $user, InvestmentClub $club, float $amount, ?string $ip = null, ?string $userAgent = null): ClubInvestment
    {
        $this->assertTermsAccepted($user);
        $this->assertClubActive($club);

        $min = (float) $club->min_investment;

        if ($amount < $min) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum investment for '.$club->name.' is '.number_format($min, 2).' USDT.',
            ]);
        }

        $max = $club->max_investment !== null ? (float) $club->max_investment : null;

        if ($max !== null && $amount > $max) {
            throw ValidationException::withMessages([
                'amount' => 'Maximum investment for '.$club->name.' is '.number_format($max, 2).' USDT.',
            ]);
        }

        if ($amount > $this->mainWallet->balance($user)) {
            throw ValidationException::withMessages([
                'amount' => 'Insufficient Main Wallet balance.',
            ]);
        }

        return DB::transaction(function () use ($user, $club, $amount) {
            $activeCount = ClubInvestment::query()
                ->where('user_id', $user->id)
                ->where('investment_club_id', $club->id)
                ->where('status', ClubInvestmentStatus::Active)
                ->count();

            if ($activeCount >= 100) {
                throw ValidationException::withMessages([
                    'amount' => 'You can only have up to 100 active investments per scheme.',
                ]);
            }

            $investment = new ClubInvestment([
                'user_id' => $user->id,
                'investment_club_id' => $club->id,
                'amount' => $amount,
                'status' => ClubInvestmentStatus::Active,
                'started_at' => now(),
                'next_reward_at' => now()->addDays((int) $club->cycle_days),
                'total_rewards_earned' => 0,
            ]);

            $investment->save();

            $this->mainWallet->debit(
                $user,
                $amount,
                TransactionType::ClubInvestment,
                'Invested in '.$club->name,
                $investment,
            );

            $this->notifications->notifyUser(
                $user,
                'investment',
                'Investment Started',
                number_format($amount, 2).' USDT transferred from Main Wallet to '.$club->name.'.',
            );

            return $investment->fresh(['club']);
        });
    }

    public function withdrawInvestment(User $user, ClubInvestment $investment): ClubCapitalRequest
    {
        if ($investment->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'amount' => 'Invalid investment selected.',
            ]);
        }

        if (! $investment->isActive()) {
            throw ValidationException::withMessages([
                'amount' => 'This investment is not active.',
            ]);
        }

        $amount = (float) $investment->amount;

        return DB::transaction(function () use ($user, $investment, $amount) {
            $hours = PlatformSettings::clubCapitalReturnHours();

            $request = ClubCapitalRequest::query()->create([
                'user_id' => $user->id,
                'club_investment_id' => $investment->id,
                'amount' => $amount,
                'status' => ClubCapitalRequestStatus::Pending,
                'requested_at' => now(),
                'available_at' => now()->addHours($hours),
            ]);

            $investment->amount = 0;
            $investment->status = ClubInvestmentStatus::Closed;
            $investment->next_reward_at = null;
            $investment->save();

            $this->notifications->notifyUser(
                $user,
                'investment',
                'Investment Withdrawal Requested',
                number_format($amount, 2).' USDT will return to your Main Wallet within '.$hours.' hours.',
            );

            return $request->fresh(['investment.club']);
        });
    }

    public function completeReadyCapitalReturns(?int $limit = null): int
    {
        $query = ClubCapitalRequest::query()
            ->with(['user', 'investment.club'])
            ->where('status', ClubCapitalRequestStatus::Pending)
            ->where('available_at', '<=', now())
            ->orderBy('available_at');

        if ($limit) {
            $query->limit($limit);
        }

        $completed = 0;

        foreach ($query->get() as $request) {
            $this->completeCapitalReturn($request);
            $completed++;
        }

        return $completed;
    }

    public function completeCapitalReturn(ClubCapitalRequest $request): ClubCapitalRequest
    {
        if ($request->status !== ClubCapitalRequestStatus::Pending) {
            return $request;
        }

        return DB::transaction(function () use ($request) {
            $locked = ClubCapitalRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ClubCapitalRequestStatus::Pending) {
                return $locked;
            }

            $user = User::query()->whereKey($locked->user_id)->lockForUpdate()->firstOrFail();
            $clubName = $locked->investment?->club?->name ?? 'Investment Scheme';

            $this->mainWallet->credit(
                $user,
                (float) $locked->amount,
                TransactionType::ClubCapitalReturn,
                'Capital returned from '.$clubName,
                $locked,
            );

            $locked->update([
                'status' => ClubCapitalRequestStatus::Completed,
                'completed_at' => now(),
            ]);

            $this->notifications->notifyUser(
                $user,
                'investment',
                'Capital Returned',
                number_format((float) $locked->amount, 2).' USDT has been credited to your Main Wallet.',
            );

            return $locked->fresh();
        });
    }

    public function acceptTerms(User $user, ?string $ip = null, ?string $userAgent = null): TermsAcceptance
    {
        return TermsAcceptance::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'document_key' => TermsDocument::InvestmentClubs->value,
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
        if (! $user->hasAcceptedTerms(TermsDocument::InvestmentClubs)) {
            throw ValidationException::withMessages([
                'terms' => 'Please accept the Investment Schemes Terms & Conditions first.',
            ]);
        }
    }

    private function assertClubActive(InvestmentClub $club): void
    {
        if (! $club->is_active) {
            throw ValidationException::withMessages([
                'club' => 'This investment scheme is currently unavailable.',
            ]);
        }
    }
}
