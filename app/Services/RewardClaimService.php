<?php

namespace App\Services;

use App\Enums\ClubInvestmentStatus;
use App\Enums\LendingPlanStatus;
use App\Enums\RewardType;
use App\Enums\TransactionType;
use App\Models\ClubInvestment;
use App\Models\LendingPlan;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RewardClaimService
{
    public const CATEGORY_SAVINGS = 'savings';

    public const CATEGORY_LENDING = 'lending';

    public const CATEGORY_SCHEME = 'scheme';

    public const CATEGORY_GAMING_REFERRAL = 'gaming_referral';

    public const CATEGORY_SCHEME_REFERRAL = 'scheme_referral';

    public function __construct(
        private readonly MainWalletService $mainWallet,
        private readonly PlatformFeeService $platformFee,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @return array<string, array{key: string, name: string, types: list<RewardType>, color: string, icon: string, timing: string}>
     */
    public static function categoryDefinitions(): array
    {
        return [
            self::CATEGORY_LENDING => [
                'key' => self::CATEGORY_LENDING,
                'name' => 'Lending Rewards',
                'types' => [RewardType::LendingInterest],
                'color' => 'violet',
                'icon' => 'gem',
                'timing' => 'Credited when a lending contract matures',
            ],
            self::CATEGORY_SCHEME => [
                'key' => self::CATEGORY_SCHEME,
                'name' => 'Investment Scheme Rewards',
                'types' => [RewardType::ClubReward, RewardType::DailyReward],
                'color' => 'orange',
                'icon' => 'chart',
                'timing' => 'Credited when each scheme cycle matures',
            ],
            self::CATEGORY_GAMING_REFERRAL => [
                'key' => self::CATEGORY_GAMING_REFERRAL,
                'name' => 'Gaming Referral Rewards',
                'types' => [RewardType::GamingReferral, RewardType::ReferralCommission],
                'color' => 'green',
                'icon' => 'game',
                'timing' => 'Credited instantly on qualifying gaming wins',
            ],
            self::CATEGORY_SCHEME_REFERRAL => [
                'key' => self::CATEGORY_SCHEME_REFERRAL,
                'name' => 'Investment Scheme Referral Rewards',
                'types' => [RewardType::InvestmentSchemeReferral],
                'color' => 'pink',
                'icon' => 'users',
                'timing' => 'Credited instantly on qualifying scheme rewards',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function retiredRewardTypeValues(): array
    {
        return [];
    }

    public function unclaimedFor(User $user): Collection
    {
        return Reward::query()
            ->where('user_id', $user->id)
            ->whereNull('claimed_at')
            ->whereNotIn('type', self::retiredRewardTypeValues())
            ->orderBy('credited_at')
            ->get();
    }

    public function unclaimedTotal(User $user): float
    {
        return round((float) Reward::query()
            ->where('user_id', $user->id)
            ->whereNull('claimed_at')
            ->whereNotIn('type', self::retiredRewardTypeValues())
            ->sum('amount'), 8);
    }

    public function requiredFee(User $user): float
    {
        return $this->platformFee->calculate($this->unclaimedTotal($user));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dashboardCategories(User $user): array
    {
        $wallet = $this->mainWallet->balance($user);
        $pendingHints = $this->pendingActivityHints($user);
        $categories = [];

        foreach (self::categoryDefinitions() as $def) {
            $typeValues = array_map(fn (RewardType $t) => $t->value, $def['types']);

            $unclaimed = round((float) Reward::query()
                ->where('user_id', $user->id)
                ->whereNull('claimed_at')
                ->whereIn('type', $typeValues)
                ->sum('amount'), 8);

            $lifetime = round((float) Reward::query()
                ->where('user_id', $user->id)
                ->whereIn('type', $typeValues)
                ->sum('amount'), 8);

            $lastCredit = Reward::query()
                ->where('user_id', $user->id)
                ->whereIn('type', $typeValues)
                ->max('credited_at');

            $fee = $this->platformFee->calculate($unclaimed);
            $status = $this->resolveStatus($unclaimed, $fee, $wallet, $pendingHints[$def['key']] ?? false);

            $categories[] = [
                ...$def,
                'balance' => $unclaimed,
                'lifetime' => $lifetime,
                'last_credit_at' => $lastCredit,
                'fee' => $fee,
                'status' => $status,
                'can_claim' => $unclaimed > 0 && $wallet + 1e-8 >= $fee,
            ];
        }

        return $categories;
    }

    public function claimCategory(User $user, string $categoryKey): array
    {
        $defs = self::categoryDefinitions();
        if (! isset($defs[$categoryKey])) {
            throw ValidationException::withMessages([
                'category' => 'Invalid reward category.',
            ]);
        }

        $types = $defs[$categoryKey]['types'];
        $label = $defs[$categoryKey]['name'];

        return $this->claimTypes($user, $types, $label);
    }

    /**
     * Claim all matured rewards: pay fee from Main Wallet, credit full reward amount.
     */
    public function claimAll(User $user): array
    {
        return $this->claimTypes($user, null, 'All rewards');
    }

    /**
     * @param  list<RewardType>|null  $types
     */
    public function claimTypes(User $user, ?array $types, string $label = 'Rewards'): array
    {
        return DB::transaction(function () use ($user, $types, $label) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $query = Reward::query()
                ->where('user_id', $lockedUser->id)
                ->whereNull('claimed_at')
                ->lockForUpdate()
                ->orderBy('id');

            if ($types !== null) {
                $query->whereIn('type', array_map(fn (RewardType $t) => $t->value, $types));
            } else {
                $query->whereNotIn('type', self::retiredRewardTypeValues());
            }

            $rewards = $query->get();

            if ($rewards->isEmpty()) {
                throw ValidationException::withMessages([
                    'rewards' => 'No '.$label.' available to claim.',
                ]);
            }

            $total = round((float) $rewards->sum('amount'), 8);
            $fee = $this->platformFee->collectFromMainWallet(
                $lockedUser,
                $total,
                null,
                'Platform Fee on '.$label.' claim ('.PlatformSettings::platformFeePercent().'%)',
            );

            $this->mainWallet->credit(
                $lockedUser,
                $total,
                TransactionType::RewardClaim,
                'Claimed '.$label.' (full amount)',
            );

            $now = now();
            foreach ($rewards as $reward) {
                $share = $total > 0 ? round(((float) $reward->amount / $total) * $fee, 8) : 0;
                $reward->update([
                    'claimed_at' => $now,
                    'platform_fee_paid' => $share,
                ]);
            }

            $this->notifications->notifyUser(
                $lockedUser,
                'reward',
                'Rewards Claimed',
                number_format($total, 2).' USDT ('.$label.') transferred to Main Wallet. Platform Fee of '
                    .number_format($fee, 2).' USDT was paid from Main Wallet.',
            );

            return [
                'reward_total' => $total,
                'platform_fee' => $fee,
                'count' => $rewards->count(),
                'label' => $label,
            ];
        });
    }

    /**
     * @return array<string, bool>
     */
    private function pendingActivityHints(User $user): array
    {
        $hasLending = LendingPlan::query()
            ->where('user_id', $user->id)
            ->where('status', LendingPlanStatus::Active)
            ->exists();

        $hasScheme = ClubInvestment::query()
            ->where('user_id', $user->id)
            ->where('status', ClubInvestmentStatus::Active)
            ->exists();

        return [
            self::CATEGORY_LENDING => $hasLending,
            self::CATEGORY_SCHEME => $hasScheme,
            self::CATEGORY_GAMING_REFERRAL => false,
            self::CATEGORY_SCHEME_REFERRAL => $hasScheme,
        ];
    }

    private function resolveStatus(float $balance, float $fee, float $wallet, bool $pendingActivity): string
    {
        if ($balance > 0) {
            return $wallet + 1e-8 >= $fee ? 'available' : 'locked';
        }

        return $pendingActivity ? 'pending' : 'locked';
    }
}
