<?php

namespace App\Http\Controllers\User;

use App\Enums\AzuboguBetStatus;
use App\Enums\ClubCapitalRequestStatus;
use App\Enums\ClubInvestmentStatus;
use App\Enums\PredictionBetStatus;
use App\Http\Controllers\Controller;
use App\Models\AzuboguBet;
use App\Models\ClubInvestment;
use App\Models\InvestmentClub;
use App\Models\PredictionBet;
use App\Models\UserNotification;
use App\Services\LeaderboardService;
use App\Services\MultiWalletService;
use App\Services\UserWalletService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly UserWalletService $wallet,
        private readonly MultiWalletService $multiWallet,
        private readonly LeaderboardService $leaderboard,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $this->multiWallet->ensureWallets($user);

        $catalog = InvestmentClub::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $investments = ClubInvestment::query()
            ->with('club')
            ->where('user_id', $user->id)
            ->where('status', ClubInvestmentStatus::Active)
            ->get();

        $pendingReturns = $user->clubCapitalRequests()
            ->with('investment.club')
            ->where('status', ClubCapitalRequestStatus::Pending)
            ->orderBy('available_at')
            ->get();

        $nextRewardAt = $investments
            ->filter(fn (ClubInvestment $inv) => $inv->next_reward_at && (float) $inv->amount > 0)
            ->sortBy('next_reward_at')
            ->first()
            ?->next_reward_at;

        $nextCapitalReturnAt = $pendingReturns->first()?->available_at;

        $announcements = UserNotification::query()
            ->where('user_id', $user->id)
            ->where('type', 'announcement')
            ->latest()
            ->limit(5)
            ->get();

        $savingsPlan = $user->activeSavingsPlan();
        if ($savingsPlan) {
            $savingsPlan->syncProgressFromDeductions();
            $savingsPlan->refresh();
        }

        $stats = $this->wallet->stats($user);
        $totalEarnings = (float) ($stats['total_rewards'] ?? 0)
            + (float) ($stats['referral_earnings'] ?? 0)
            + (float) ($stats['gaming_wins'] ?? 0);
        $earningsCharts = $this->earningsCharts($user->id);
        $leaderboardPreview = $this->designLeaderboardPreview();

        return view('user.dashboard.index', [
            'user' => $user,
            'stats' => $stats,
            'walletCards' => $this->multiWallet->walletCards($user),
            'totalEarnings' => $totalEarnings,
            'catalog' => $catalog,
            'investments' => $investments,
            'pendingReturns' => $pendingReturns,
            'savingsPlan' => $savingsPlan,
            'savingsProgress' => $savingsPlan?->daysProgressPercent() ?? 0,
            'savingsSavedProgress' => $savingsPlan?->savedProgressPercent() ?? 0,
            'nextRewardAt' => $nextRewardAt instanceof Carbon ? $nextRewardAt : null,
            'nextCapitalReturnAt' => $nextCapitalReturnAt instanceof Carbon ? $nextCapitalReturnAt : null,
            'nextWithdrawalAt' => $this->wallet->nextWithdrawalAt($user),
            'canWithdraw' => $this->wallet->canWithdraw($user),
            'lastWithdrawal' => $this->wallet->lastCompletedWithdrawal($user),
            'transactions' => $user->transactions()->latest()->limit(5)->get(),
            'referralStats' => $this->wallet->referralStats($user),
            'announcements' => $announcements,
            'promoSlides' => $this->promoSlides($announcements),
            'leaderboardPreview' => $leaderboardPreview,
            'homeGames' => $this->homeGames(),
            'moreMenuItems' => $this->moreMenuItems(),
            'gameStats' => $this->gameStats($user->id),
            'earningsChart' => $earningsCharts['month'],
            'earningsCharts' => $earningsCharts,
            'progress' => $this->progressBadge($stats, $totalEarnings),
            'referralLink' => url('/register?ref='.$user->referral_code),
        ]);
    }

    /**
     * @return list<array{key: string, title: string, tagline: string, url: string, image: string, image_desktop: string, image_mobile: string, alt: string, players: int}>
     */
    private function homeGames(): array
    {
        $v4 = function (string $file) {
            $path = public_path('images/dashboard/v4/'.$file);
            $v = is_file($path) ? filemtime($path) : time();

            return asset('images/dashboard/v4/'.$file).'?v='.$v;
        };

        return [
            [
                'key' => 'prediction',
                'title' => 'PREDICTION DUEL',
                'tagline' => 'Two Outcomes. Bigger Wins.',
                'url' => route('user.prediction.index'),
                'image' => $v4('home-game-prediction.png'),
                'image_desktop' => $v4('home-game-prediction.png'),
                'image_mobile' => $v4('prediction-duel-mobile.png'),
                'alt' => 'Prediction Duel',
                'players' => 2232,
            ],
            [
                'key' => 'azubogu',
                'title' => 'AZUBOGU GAME™',
                'tagline' => '12 Numbers. Real Multipliers.',
                'url' => route('user.azubogu.index'),
                'image' => $v4('home-game-azubogu.png'),
                'image_desktop' => $v4('home-game-azubogu.png'),
                'image_mobile' => $v4('azubogu-mobile.png'),
                'alt' => 'Azubogu Game',
                'players' => 1865,
            ],
            [
                'key' => 'rpsv',
                'title' => 'RPSV™',
                'tagline' => 'Rock Paper Scissors Versus.',
                'url' => route('user.rpsv.index'),
                'image' => $v4('home-game-rpsv.png'),
                'image_desktop' => $v4('home-game-rpsv.png'),
                'image_mobile' => $v4('rpsv-mobile.png'),
                'alt' => 'RPSV',
                'players' => 1493,
            ],
        ];
    }

    /**
     * @return list<array{label: string, icon: string, url: string}>
     */
    private function moreMenuItems(): array
    {
        return [
            ['label' => 'Deposit', 'icon' => 'fa-plus', 'url' => route('user.deposit.index')],
            ['label' => 'Withdraw', 'icon' => 'fa-arrow-up-from-bracket', 'url' => route('user.withdraw.index')],
            ['label' => 'Lending', 'icon' => 'fa-hand-holding-dollar', 'url' => route('user.lending.index')],
            ['label' => 'Referrals', 'icon' => 'fa-users', 'url' => route('user.referrals.index')],
            ['label' => 'Earnings', 'icon' => 'fa-chart-line', 'url' => route('user.earnings.index')],
            ['label' => 'Transactions', 'icon' => 'fa-receipt', 'url' => route('user.transactions.index')],
            ['label' => 'Chat', 'icon' => 'fa-comments', 'url' => route('user.chat.index')],
            ['label' => 'Support', 'icon' => 'fa-headset', 'url' => route('user.support.index')],
            ['label' => 'Profile', 'icon' => 'fa-user', 'url' => route('user.profile.edit')],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, UserNotification>  $announcements
     * @return list<array{eyebrow: string, title: string, title_accent?: string, copy: string, cta: string, url: string, image: string, image_desktop: string, image_mobile: string, alt: string}>
     */
    private function promoSlides($announcements): array
    {
        $v4 = function (string $file) {
            $path = public_path('images/dashboard/v4/'.$file);
            $v = is_file($path) ? filemtime($path) : time();

            return asset('images/dashboard/v4/'.$file).'?v='.$v;
        };

        $slides = [
            [
                'eyebrow' => '',
                'title' => "PLAY BIG.\nWIN BIGGER.",
                'title_lines' => [
                    ['text' => 'PLAY BIG.', 'tone' => 'white'],
                    ['text' => 'WIN BIGGER.', 'tone' => 'gold'],
                ],
                'title_accent' => '',
                'copy' => 'Enter Games Arena — Prediction Duel, Azubogu Game & RPSV.',
                'cta' => 'PLAY GAMES',
                'url' => route('user.games.index'),
                'image' => $v4('hero-desktop.png'),
                'image_desktop' => $v4('hero-desktop.png'),
                'image_mobile' => $v4('hero-mobile.png'),
                'alt' => 'Games banner',
            ],
            [
                'eyebrow' => '',
                'title' => "YOUR PLATFORM.\nYOUR POWER.\nYOUR REWARDS.",
                'title_lines' => [
                    ['text' => 'YOUR PLATFORM.', 'tone' => 'white'],
                    ['text' => 'YOUR POWER.', 'tone' => 'gold'],
                    ['text' => 'YOUR REWARDS.', 'tone' => 'gold'],
                ],
                'title_accent' => '',
                'copy' => 'Manage, Play, Invest & Earn All in One Contract.',
                'cta' => 'EXPLORE NOW',
                'url' => route('user.clubs.index'),
                'image' => $v4('hero-platform-desktop.png'),
                'image_desktop' => $v4('hero-platform-desktop.png'),
                'image_mobile' => $v4('hero-platform-mobile.png'),
                'alt' => 'Platform banner',
            ],
            [
                'eyebrow' => '',
                'title' => "INVEST SMART.\nEARN FASTER.",
                'title_lines' => [
                    ['text' => 'INVEST SMART.', 'tone' => 'white'],
                    ['text' => 'EARN FASTER.', 'tone' => 'gold'],
                ],
                'title_accent' => '',
                'copy' => 'Grow with Lending and Investment Schemes.',
                'cta' => 'START INVESTING',
                'url' => route('user.clubs.index'),
                'image' => $v4('hero-invest-desktop.png'),
                'image_desktop' => $v4('hero-invest-desktop.png'),
                'image_mobile' => $v4('hero-invest-mobile.png'),
                'alt' => 'Invest banner',
            ],
        ];

        return $slides;
    }

    /**
     * Home mock leaderboard (exact design preview).
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function designLeaderboardPreview()
    {
        $avatar = function (string $file) {
            $path = public_path('images/dashboard/v4/'.$file);
            if (! is_file($path)) {
                return null;
            }

            return asset('images/dashboard/v4/'.$file).'?v='.filemtime($path);
        };

        return collect([
            (object) ['rank' => 1, 'name' => 'Pink seven', 'avatar' => $avatar('home-lb-1.png'), 'winnings' => 96.45],
            (object) ['rank' => 2, 'name' => 'GoldMaster', 'avatar' => $avatar('home-lb-2.png'), 'winnings' => 82.10],
            (object) ['rank' => 3, 'name' => 'EagleWin', 'avatar' => $avatar('home-lb-3.png'), 'winnings' => 74.25],
            (object) ['rank' => 4, 'name' => 'ShadowBet', 'avatar' => $avatar('home-lb-4.png'), 'winnings' => 61.50],
            (object) ['rank' => 5, 'name' => 'CryptoKing', 'avatar' => $avatar('home-lb-5.png'), 'winnings' => 58.00],
        ]);
    }

    /**
     * @return array{total_bets: int, wins: int, win_rate: float, biggest_win: float}
     */
    private function gameStats(int $userId): array
    {
        $predQuery = PredictionBet::query()
            ->where('user_id', $userId)
            ->whereIn('status', [PredictionBetStatus::Won, PredictionBetStatus::Lost]);

        $azuQuery = AzuboguBet::query()
            ->where('user_id', $userId)
            ->whereIn('status', [AzuboguBetStatus::Won, AzuboguBetStatus::Lost]);

        $predTotal = (clone $predQuery)->count();
        $azuTotal = (clone $azuQuery)->count();
        $predWins = (clone $predQuery)->where('status', PredictionBetStatus::Won)->count();
        $azuWins = (clone $azuQuery)->where('status', AzuboguBetStatus::Won)->count();

        $totalBets = $predTotal + $azuTotal;
        $wins = $predWins + $azuWins;
        $winRate = $totalBets > 0 ? round(($wins / $totalBets) * 100, 1) : 0.0;

        $biggestPred = (float) PredictionBet::query()
            ->where('user_id', $userId)
            ->where('status', PredictionBetStatus::Won)
            ->max('payout');

        $biggestAzu = (float) AzuboguBet::query()
            ->where('user_id', $userId)
            ->where('status', AzuboguBetStatus::Won)
            ->max('payout');

        return [
            'total_bets' => $totalBets,
            'wins' => $wins,
            'win_rate' => $winRate,
            'biggest_win' => max($biggestPred, $biggestAzu),
        ];
    }

    /**
     * @return array{
     *   week: array{labels: list<string>, values: list<float>, total: float, change_pct: float},
     *   month: array{labels: list<string>, values: list<float>, total: float, change_pct: float}
     * }
     */
    private function earningsCharts(int $userId): array
    {
        return [
            'week' => $this->earningsChartForRange($userId, 7),
            'month' => $this->earningsChartForRange($userId, 30),
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<float>, total: float, change_pct: float}
     */
    private function earningsChartForRange(int $userId, int $days): array
    {
        $user = \App\Models\User::query()->findOrFail($userId);
        $points = $this->wallet->earningsChart($user, $days);
        $dayCount = count($points);

        $labels = [];
        $values = [];

        foreach ($points as $i => $point) {
            $date = Carbon::parse($point['date']);
            if ($dayCount > 14) {
                $labels[] = ($i === 0 || $i === $dayCount - 1 || $i % 5 === 0)
                    ? (string) $date->format('j')
                    : '';
            } else {
                $labels[] = $date->format('D');
            }
            $values[] = round((float) ($point['total'] ?? 0), 2);
        }

        $total = array_sum($values);
        // Compare recent half vs previous half of the window (dynamic earnings %).
        $half = max(1, (int) floor(count($values) / 2));
        $prev = array_sum(array_slice($values, 0, $half));
        $recent = array_sum(array_slice($values, -$half));
        if ($prev > 0.00001) {
            $changePct = round((($recent - $prev) / $prev) * 100, 1);
        } elseif ($recent > 0) {
            $changePct = 100.0;
        } elseif ($recent < 0) {
            $changePct = -100.0;
        } else {
            $changePct = 0.0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
            'total' => round($total, 2),
            'change_pct' => $changePct,
            'recent_total' => round($recent, 2),
            'previous_total' => round($prev, 2),
        ];
    }

    /**
     * Soft progression badge for the welcome hero (display-only).
     *
     * @param  array<string, mixed>  $stats
     * @return array{vip: int, level: int, xp: int, xp_next: int, xp_pct: float, remaining: int}
     */
    private function progressBadge(array $stats, float $totalEarnings): array
    {
        $deposited = (float) ($stats['total_deposited'] ?? 0);
        $invested = (float) ($stats['total_invested'] ?? 0);
        $xp = (int) floor(($deposited * 2) + ($invested * 1.5) + ($totalEarnings * 3));

        $level = max(1, (int) floor($xp / 500) + 1);
        $xpFloor = ($level - 1) * 500;
        $xpNext = $level * 500;
        $intoLevel = $xp - $xpFloor;
        $span = max(1, $xpNext - $xpFloor);
        $xpPct = min(100, round(($intoLevel / $span) * 100, 1));

        $vip = match (true) {
            $deposited >= 10000 || $invested >= 5000 => 5,
            $deposited >= 5000 || $invested >= 2500 => 4,
            $deposited >= 2000 || $invested >= 1000 => 3,
            $deposited >= 500 || $invested >= 250 => 2,
            default => 1,
        };

        return [
            'vip' => $vip,
            'level' => $level,
            'xp' => $xp,
            'xp_next' => $xpNext,
            'xp_pct' => $xpPct,
            'remaining' => max(0, $xpNext - $xp),
        ];
    }
}
