<?php

namespace App\Services;

use App\Enums\AzuboguBetStatus;
use App\Enums\AzuboguRoundStatus;
use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\AzuboguBet;
use App\Models\AzuboguRound;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AzuboguGameService
{
    public function __construct(
        private readonly MultiWalletService $wallets,
        private readonly WalletLiveRateService $rates,
        private readonly AzuboguFairService $fair,
        private readonly NotificationService $notifications,
        private readonly ReferralCommissionService $referrals,
    ) {}

    /**
     * Continuous live loop: betting → settle/reveal → next round.
     */
    public function tick(): ?AzuboguRound
    {
        return DB::transaction(function () {
            AzuboguRound::query()->orderByDesc('id')->lockForUpdate()->first();

            $round = AzuboguRound::query()
                ->whereIn('status', [AzuboguRoundStatus::Betting, AzuboguRoundStatus::Settling])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $now = now();

            if ($round) {
                if ($round->status === AzuboguRoundStatus::Betting && $round->betting_ends_at->lte($now)) {
                    $this->settleRound($round);
                    $round = $round->fresh();
                }

                if ($round && $round->status === AzuboguRoundStatus::Settling && $round->round_ends_at->lte($now)) {
                    $round->update(['status' => AzuboguRoundStatus::Completed]);
                    $round = null;
                }
            }

            if (! $round) {
                $stillLive = AzuboguRound::query()
                    ->whereIn('status', [AzuboguRoundStatus::Betting, AzuboguRoundStatus::Settling])
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                $round = $stillLive ?: $this->createRound();
            }

            return $round->fresh();
        });
    }

    public function createRound(): AzuboguRound
    {
        $betSeconds = PlatformSettings::azuboguBettingSeconds();
        $settleSeconds = PlatformSettings::azuboguSettleSeconds();
        $serverSeed = $this->fair->generateServerSeed();
        $started = now();
        $lastNumber = (int) AzuboguRound::query()->max('round_number');

        return AzuboguRound::query()->create([
            'round_number' => $lastNumber + 1,
            'status' => AzuboguRoundStatus::Betting,
            'server_seed' => $serverSeed,
            'server_seed_hash' => $this->fair->hashServerSeed($serverSeed),
            'client_seed' => $this->fair->generateClientSeed(),
            'nonce' => $lastNumber + 1,
            'multipliers' => null,
            'total_bets' => 0,
            'total_payout' => 0,
            'total_players' => 0,
            'betting_ends_at' => $started->copy()->addSeconds($betSeconds),
            'round_ends_at' => $started->copy()->addSeconds($betSeconds + $settleSeconds),
            'started_at' => $started,
        ]);
    }

    /**
     * @param  list<int>  $numbers
     */
    public function placeBet(User $user, array $numbers, float $amount): AzuboguBet
    {
        $numbers = array_values(array_unique(array_map('intval', $numbers)));
        sort($numbers);

        if (count($numbers) < 1 || count($numbers) > 3) {
            throw ValidationException::withMessages([
                'numbers' => 'Select between 1 and 3 numbers.',
            ]);
        }

        foreach ($numbers as $n) {
            if ($n < 1 || $n > 12) {
                throw ValidationException::withMessages([
                    'numbers' => 'Numbers must be between 1 and 12.',
                ]);
            }
        }

        $currency = $user->preferredWalletCurrency();
        $amount = round(max(0, $amount), $currency->decimals());
        $amountUsd = $this->rates->amountToUsd($currency, $amount);
        $minUsd = PlatformSettings::azuboguMinBet();
        $maxUsd = PlatformSettings::azuboguMaxBet();

        if ($amountUsd < $minUsd || $amountUsd > $maxUsd) {
            $minNative = $this->rates->cryptoAmountForUsd($currency, $minUsd);
            $maxNative = $this->rates->cryptoAmountForUsd($currency, $maxUsd);

            throw ValidationException::withMessages([
                'amount' => 'Bet must be between '.$currency->format($minNative).' and '.$currency->format($maxNative).'.',
            ]);
        }

        return DB::transaction(function () use ($user, $numbers, $amount, $currency) {
            // Lock only the user row — avoid waiting on tick()'s round lock.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $round = AzuboguRound::query()
                ->where('status', AzuboguRoundStatus::Betting)
                ->orderByDesc('id')
                ->first();

            // 2s grace so in-flight Place Bet still lands near timer end.
            if (! $round || $round->betting_ends_at->lt(now()->subSeconds(2))) {
                throw ValidationException::withMessages([
                    'amount' => 'Betting is closed for this round. Wait for the next round.',
                ]);
            }

            $existing = AzuboguBet::query()
                ->where('user_id', $user->id)
                ->where('azubogu_round_id', $round->id)
                ->where('status', AzuboguBetStatus::Active)
                ->lockForUpdate()
                ->first();

            // Selecting more/fewer numbers updates the active bet (amount stays).
            if ($existing) {
                $existing->numbers = $numbers;
                $existing->save();

                return $existing->fresh();
            }

            if ($amount > 0 && $amount > $this->wallets->balance($user, $currency)) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient '.$currency->value.' wallet balance.',
                ]);
            }

            // Re-check round still open after user lock (tick may have settled).
            $round->refresh();
            if ($round->status !== AzuboguRoundStatus::Betting) {
                throw ValidationException::withMessages([
                    'amount' => 'Betting is closed for this round. Wait for the next round.',
                ]);
            }

            $payload = [
                'user_id' => $user->id,
                'azubogu_round_id' => $round->id,
                'numbers' => $numbers,
                'amount' => $amount,
                'status' => AzuboguBetStatus::Active,
            ];

            if ($this->betsTrackCurrency()) {
                $payload['currency'] = $currency->value;
            }

            $bet = AzuboguBet::query()->create($payload);

            if ($amount > 0) {
                $this->wallets->debit(
                    $user,
                    $currency,
                    $amount,
                    TransactionType::AzuboguBet,
                    'Azubogu bet round #'.$round->round_number.' on '.implode(',', $numbers),
                    $bet,
                );
            }

            $this->recalcRoundTotals($round);

            return $bet->fresh();
        });
    }

    public function cancelBet(User $user): ?AzuboguBet
    {
        return DB::transaction(function () use ($user) {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $round = AzuboguRound::query()
                ->where('status', AzuboguRoundStatus::Betting)
                ->orderByDesc('id')
                ->first();

            if (! $round || $round->betting_ends_at->lt(now()->subSeconds(2))) {
                throw ValidationException::withMessages([
                    'bet' => 'Cannot cancel — betting is closed.',
                ]);
            }

            $bet = AzuboguBet::query()
                ->where('user_id', $user->id)
                ->where('azubogu_round_id', $round->id)
                ->where('status', AzuboguBetStatus::Active)
                ->lockForUpdate()
                ->first();

            if (! $bet) {
                return null;
            }

            $round->refresh();
            if ($round->status !== AzuboguRoundStatus::Betting) {
                throw ValidationException::withMessages([
                    'bet' => 'Cannot cancel — betting is closed.',
                ]);
            }

            $amount = (float) $bet->amount;
            $currency = $this->betCurrency($bet);
            $bet->update(['status' => AzuboguBetStatus::Cancelled]);

            if ($amount > 0) {
                $this->wallets->credit(
                    $user,
                    $currency,
                    $amount,
                    TransactionType::AzuboguBetRefund,
                    'Azubogu bet cancelled #'.$round->round_number,
                    $bet,
                );
            }

            $this->recalcRoundTotals($round);

            return $bet->fresh();
        });
    }

    public function settleRound(AzuboguRound $round): void
    {
        if ($round->status !== AzuboguRoundStatus::Betting) {
            return;
        }

        $multipliers = $this->fair->resolveMultipliers(
            $round->server_seed,
            $round->client_seed,
            (int) $round->nonce,
        );

        $bets = AzuboguBet::query()
            ->with('user')
            ->where('azubogu_round_id', $round->id)
            ->where('status', AzuboguBetStatus::Active)
            ->lockForUpdate()
            ->get();

        $totalPayout = 0.0;

        foreach ($bets as $bet) {
            $selected = array_map('intval', $bet->numbers ?? []);
            $combined = $this->fair->combinedMultiplier($selected, $multipliers);
            $payout = round((float) $bet->amount * $combined, 8);
            $isWin = $combined > 0;
            $currency = $this->betCurrency($bet);

            if ($isWin) {
                $bet->update([
                    'status' => AzuboguBetStatus::Won,
                    'combined_multiplier' => $combined,
                    'payout' => $payout,
                ]);

                if ($payout > 0) {
                    $this->wallets->credit(
                        $bet->user,
                        $currency,
                        $payout,
                        TransactionType::AzuboguWin,
                        'Azubogu win round #'.$round->round_number.' ('.$combined.'x)',
                        $bet,
                    );

                    $totalPayout += $payout;

                    $profit = max(0, round($payout - (float) $bet->amount, 8));
                    if ($profit > 0) {
                        $profitUsd = $this->rates->amountToUsd($currency, $profit);
                        $this->referrals->creditForGamingWin($bet->user, $profitUsd, now(), 'azubogu prediction game');
                    }

                    $this->notifications->notifyUser(
                        $bet->user,
                        'azubogu',
                        'Azubogu Win',
                        'You won '.$currency->format($payout).' on round #'.$round->round_number.' ('.$combined.'×).',
                        ['url' => route('user.azubogu.index')],
                    );
                } else {
                    $this->notifications->notifyUser(
                        $bet->user,
                        'azubogu',
                        'Azubogu Result',
                        'Round #'.$round->round_number.': result '.$combined.'×. $0 play — no wallet credit.',
                        ['url' => route('user.azubogu.index')],
                    );
                }
            } else {
                $bet->update([
                    'status' => AzuboguBetStatus::Lost,
                    'combined_multiplier' => $combined,
                    'payout' => 0,
                ]);

                $this->notifications->notifyUser(
                    $bet->user,
                    'azubogu',
                    'Azubogu Loss',
                    'Round #'.$round->round_number.': payout was 0 (a selected number hit 0× or product was zero).',
                    ['url' => route('user.azubogu.index')],
                );
            }
        }

        $round->update([
            'status' => AzuboguRoundStatus::Settling,
            'multipliers' => $multipliers,
            'total_payout' => $totalPayout,
            'settled_at' => now(),
        ]);
    }

    private function recalcRoundTotals(AzuboguRound $round): void
    {
        $active = AzuboguBet::query()
            ->where('azubogu_round_id', $round->id)
            ->where('status', AzuboguBetStatus::Active);

        $round->update([
            'total_bets' => (clone $active)->sum('amount'),
            'total_players' => (clone $active)->distinct('user_id')->count('user_id'),
        ]);
    }

    /**
     * Read current live round without locking (fast path for UI polls).
     */
    public function liveRound(): ?AzuboguRound
    {
        return AzuboguRound::query()
            ->whereIn('status', [AzuboguRoundStatus::Betting, AzuboguRoundStatus::Settling])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Only lock/settle when the phase actually needs to advance.
     */
    public function advanceIfDue(): ?AzuboguRound
    {
        $round = $this->liveRound();
        $now = now();

        $needsTick = ! $round
            || ($round->status === AzuboguRoundStatus::Betting && $round->betting_ends_at->lte($now))
            || ($round->status === AzuboguRoundStatus::Settling && $round->round_ends_at->lte($now));

        return $needsTick ? $this->tick() : $round;
    }

    public function stateFor(?User $user = null, bool $advance = true): array
    {
        $round = $advance ? $this->advanceIfDue() : $this->liveRound();
        $now = now();

        $userBet = null;
        if ($round && $user) {
            $userBet = AzuboguBet::query()
                ->where('user_id', $user->id)
                ->where('azubogu_round_id', $round->id)
                ->whereIn('status', [AzuboguBetStatus::Active, AzuboguBetStatus::Won, AzuboguBetStatus::Lost])
                ->first();
        }

        $history = AzuboguRound::query()
            ->whereNotNull('multipliers')
            ->whereIn('status', [AzuboguRoundStatus::Settling, AzuboguRoundStatus::Completed])
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(function (AzuboguRound $r) {
                return [
                    'round_number' => (int) $r->round_number,
                    'time' => $r->settled_at?->timezone(config('app.timezone'))->format('g:i A') ?? '',
                    'groups' => $r->groupedMultipliers(),
                    'multipliers' => $r->multipliers,
                ];
            })
            ->values()
            ->all();

        $myBets = [];
        if ($user) {
            $myBets = AzuboguBet::query()
                ->with('round:id,round_number')
                ->where('user_id', $user->id)
                ->whereIn('status', [AzuboguBetStatus::Won, AzuboguBetStatus::Lost, AzuboguBetStatus::Active])
                ->latest('id')
                ->limit(5)
                ->get()
                ->map(fn (AzuboguBet $b) => [
                    'round' => (int) ($b->round?->round_number ?? 0),
                    'numbers' => $b->numbers,
                    'amount' => (float) $b->amount,
                    'payout' => (float) ($b->payout ?? 0),
                    'multiplier' => (float) ($b->combined_multiplier ?? 0),
                    'status' => $b->status->value,
                ])
                ->all();
        }

        $serverSeedVisible = $round && $round->status !== AzuboguRoundStatus::Betting
            ? $round->server_seed
            : null;

        return [
            'round' => $round ? [
                'id' => $round->id,
                'round_number' => (int) $round->round_number,
                'status' => $round->status->value,
                'server_seed_hash' => $round->server_seed_hash,
                'server_seed' => $serverSeedVisible,
                'client_seed' => $round->client_seed,
                'nonce' => (int) $round->nonce,
                'multipliers' => $round->multipliers,
                'groups' => $round->multipliers ? $round->groupedMultipliers() : null,
                'betting_seconds_left' => max(0, $round->betting_ends_at->getTimestamp() - $now->getTimestamp()),
                'round_seconds_left' => max(0, $round->round_ends_at->getTimestamp() - $now->getTimestamp()),
                'betting_ends_at' => $round->betting_ends_at->toIso8601String(),
                'round_ends_at' => $round->round_ends_at->toIso8601String(),
            ] : null,
            'stats' => [
                'total_pool' => (float) ($round?->total_bets ?? 0),
                'players' => (int) ($round?->total_players ?? 0),
            ],
            'config' => [
                'min_bet' => PlatformSettings::azuboguMinBet(),
                'max_bet' => PlatformSettings::azuboguMaxBet(),
                'betting_seconds' => PlatformSettings::azuboguBettingSeconds(),
                'settle_seconds' => PlatformSettings::azuboguSettleSeconds(),
            ],
            'user_bet' => $userBet ? [
                'numbers' => $userBet->numbers,
                'amount' => (float) $userBet->amount,
                'currency' => $this->betCurrency($userBet)->value,
                'payout' => (float) ($userBet->payout ?? 0),
                'multiplier' => (float) ($userBet->combined_multiplier ?? 0),
                'status' => $userBet->status->value,
            ] : null,
            'play_currency' => $user ? $user->preferredWalletCurrency()->value : WalletCurrency::Usdt->value,
            'wallet' => $user ? $this->wallets->balance($user, $user->preferredWalletCurrency()) : 0,
            'history' => $history,
            'my_bets' => $myBets,
        ];
    }

    private function betCurrency(AzuboguBet $bet): WalletCurrency
    {
        if ($this->betsTrackCurrency()) {
            return WalletCurrency::tryNormalize((string) ($bet->currency ?? ''))
                ?? WalletCurrency::Usdt;
        }

        return WalletCurrency::Usdt;
    }

    private function betsTrackCurrency(): bool
    {
        return Schema::hasColumn('azubogu_bets', 'currency');
    }
}
