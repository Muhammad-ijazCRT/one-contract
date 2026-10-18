<?php

namespace App\Services;

use App\Enums\AzuboguBetStatus;
use App\Enums\ClubInvestmentStatus;
use App\Enums\LendingPlanStatus;
use App\Enums\PredictionBetStatus;
use App\Enums\SavingsPlanStatus;
use App\Models\AzuboguBet;
use App\Models\ClubInvestment;
use App\Models\LendingPlan;
use App\Models\PredictionBet;
use App\Models\Referral;
use App\Models\RpsvMatch;
use App\Models\SavingsPlan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    /**
     * @return array{rows: Collection<int, object>, my_rank: ?array{rank: int|null, wins: int, volume: float, winnings: float, name: string}}
     */
    public function gameLeaders(string $period = 'all', int $limit = 50, ?User $viewer = null): array
    {
        [$from] = $this->periodBounds($period);

        $prediction = PredictionBet::query()
            ->select([
                'user_id',
                DB::raw("SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as wins"),
                DB::raw("SUM(CASE WHEN status = 'won' THEN COALESCE(payout, amount) ELSE 0 END) as winnings"),
                DB::raw('SUM(amount) as volume'),
            ])
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->whereIn('status', [
                PredictionBetStatus::Won->value,
                PredictionBetStatus::Lost->value,
                PredictionBetStatus::Cancelled->value,
                PredictionBetStatus::Active->value,
            ])
            ->groupBy('user_id');

        $azubogu = AzuboguBet::query()
            ->select([
                'user_id',
                DB::raw("SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as wins"),
                DB::raw("SUM(CASE WHEN status = 'won' THEN COALESCE(payout, amount) ELSE 0 END) as winnings"),
                DB::raw('SUM(amount) as volume'),
            ])
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->whereIn('status', [
                AzuboguBetStatus::Won->value,
                AzuboguBetStatus::Lost->value,
                AzuboguBetStatus::Cancelled->value,
                AzuboguBetStatus::Active->value,
            ])
            ->groupBy('user_id');

        $rpsv = RpsvMatch::query()
            ->select([
                'winner_id as user_id',
                DB::raw('COUNT(*) as wins'),
                DB::raw('COALESCE(SUM(winner_payout), 0) as winnings'),
                DB::raw('COALESCE(SUM(stake), 0) as volume'),
            ])
            ->whereNotNull('winner_id')
            ->when($from, fn ($q) => $q->where('updated_at', '>=', $from))
            ->groupBy('winner_id');

        $limit = max(1, min(500, $limit));
        $combined = $prediction->toBase()
            ->unionAll($azubogu->toBase())
            ->unionAll($rpsv->toBase());

        $rows = DB::query()
            ->fromSub($combined, 'g')
            ->selectRaw('user_id, SUM(wins) as wins, SUM(winnings) as winnings, SUM(volume) as volume')
            ->groupBy('user_id')
            ->havingRaw('SUM(wins) > 0 OR SUM(volume) > 0 OR SUM(winnings) > 0')
            ->orderByDesc('winnings')
            ->orderByDesc('wins')
            ->orderByDesc('volume')
            ->limit($limit)
            ->get();

        return $this->hydrateGameRows($rows, $viewer);
    }

    /**
     * @return array{rows: Collection<int, object>, my_rank: ?array{rank: int|null, amount: float, name: string}}
     */
    public function savingsLeaders(int $limit = 50, ?User $viewer = null): array
    {
        $rows = SavingsPlan::query()
            ->select([
                'user_id',
                DB::raw('SUM(total_saved) as amount'),
            ])
            ->whereIn('status', [
                SavingsPlanStatus::Active->value,
                SavingsPlanStatus::Matured->value,
                SavingsPlanStatus::Completed->value,
            ])
            ->groupBy('user_id')
            ->havingRaw('SUM(total_saved) > 0')
            ->orderByDesc('amount')
            ->limit($limit)
            ->get();

        return $this->hydrateAmountRows($rows, 'amount', $viewer);
    }

    /**
     * @return array{rows: Collection<int, object>, my_rank: ?array{rank: int|null, amount: float, name: string}}
     */
    public function lendingLeaders(int $limit = 50, ?User $viewer = null): array
    {
        $rows = LendingPlan::query()
            ->select([
                'user_id',
                DB::raw('SUM(principal) as amount'),
            ])
            ->whereIn('status', [
                LendingPlanStatus::Active->value,
                LendingPlanStatus::Matured->value,
                LendingPlanStatus::Completed->value,
            ])
            ->groupBy('user_id')
            ->havingRaw('SUM(principal) > 0')
            ->orderByDesc('amount')
            ->limit($limit)
            ->get();

        return $this->hydrateAmountRows($rows, 'amount', $viewer);
    }

    /**
     * @return array{rows: Collection<int, object>, my_rank: ?array{rank: int|null, amount: float, name: string}}
     */
    public function investmentLeaders(int $limit = 50, ?User $viewer = null): array
    {
        $rows = ClubInvestment::query()
            ->select([
                'user_id',
                DB::raw('SUM(amount) as amount'),
            ])
            ->where('status', ClubInvestmentStatus::Active->value)
            ->where('amount', '>', 0)
            ->groupBy('user_id')
            ->orderByDesc('amount')
            ->limit($limit)
            ->get();

        return $this->hydrateAmountRows($rows, 'amount', $viewer);
    }

    /**
     * @return Collection<int, object>
     */
    public function referralLeaders(int $limit = 50): Collection
    {
        $rows = Referral::query()
            ->select(['referrer_id', DB::raw('COUNT(*) as total')])
            ->where('level', 1)
            ->groupBy('referrer_id')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        $users = User::query()
            ->whereIn('id', $rows->pluck('referrer_id'))
            ->get(['id', 'name', 'member_id', 'avatar'])
            ->keyBy('id');

        return $rows->values()->map(function ($row, $index) use ($users) {
            $user = $users->get($row->referrer_id);

            return (object) [
                'rank' => $index + 1,
                'user_id' => (int) $row->referrer_id,
                'name' => $user?->name ?? 'Player',
                'member_id' => $user?->member_id,
                'avatar' => $user?->avatarUrl(),
                'total' => (int) $row->total,
            ];
        });
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array{rows: Collection<int, object>, my_rank: ?array{rank: int|null, wins: int, volume: float, winnings: float, name: string}}
     */
    private function hydrateGameRows(Collection $rows, ?User $viewer): array
    {
        $users = User::query()
            ->whereIn('id', $rows->pluck('user_id'))
            ->get(['id', 'name', 'member_id', 'avatar'])
            ->keyBy('id');

        $ranked = $rows->values()->map(function ($row, $index) use ($users) {
            $user = $users->get($row->user_id);

            return (object) [
                'rank' => $index + 1,
                'user_id' => (int) $row->user_id,
                'name' => $user?->name ?? 'Player',
                'member_id' => $user?->member_id,
                'avatar' => $user?->avatarUrl(),
                'wins' => (int) $row->wins,
                'winnings' => (float) ($row->winnings ?? 0),
                'volume' => (float) $row->volume,
            ];
        });

        $myRank = null;
        if ($viewer) {
            $mine = $ranked->firstWhere('user_id', $viewer->id);
            $myRank = $mine ? [
                'rank' => $mine->rank,
                'wins' => $mine->wins,
                'volume' => $mine->volume,
                'winnings' => $mine->winnings,
                'name' => $mine->name,
            ] : [
                'rank' => null,
                'wins' => 0,
                'volume' => 0.0,
                'winnings' => 0.0,
                'name' => $viewer->name,
            ];
        }

        return ['rows' => $ranked, 'my_rank' => $myRank];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return array{rows: Collection<int, object>, my_rank: ?array{rank: int|null, amount: float, name: string}}
     */
    private function hydrateAmountRows(Collection $rows, string $amountKey, ?User $viewer): array
    {
        $users = User::query()
            ->whereIn('id', $rows->pluck('user_id'))
            ->get(['id', 'name', 'member_id', 'avatar'])
            ->keyBy('id');

        $ranked = $rows->values()->map(function ($row, $index) use ($users, $amountKey) {
            $user = $users->get($row->user_id);

            return (object) [
                'rank' => $index + 1,
                'user_id' => (int) $row->user_id,
                'name' => $user?->name ?? 'Player',
                'member_id' => $user?->member_id,
                'avatar' => $user?->avatarUrl(),
                'amount' => (float) $row->{$amountKey},
            ];
        });

        $myRank = null;
        if ($viewer) {
            $mine = $ranked->firstWhere('user_id', $viewer->id);
            $myRank = $mine ? [
                'rank' => $mine->rank,
                'amount' => $mine->amount,
                'name' => $mine->name,
            ] : [
                'rank' => null,
                'amount' => 0.0,
                'name' => $viewer->name,
            ];
        }

        return ['rows' => $ranked, 'my_rank' => $myRank];
    }

    /**
     * @return array{0: ?\Carbon\Carbon, 1: ?\Carbon\Carbon}
     */
    private function periodBounds(string $period): array
    {
        return match ($period) {
            'week' => [now()->subDays(7), now()->subDays(7)],
            'month' => [now()->subDays(30), now()->subDays(30)],
            default => [null, null],
        };
    }
}
