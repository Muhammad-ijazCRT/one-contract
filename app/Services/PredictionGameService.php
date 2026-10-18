<?php

namespace App\Services;

use App\Enums\PredictionBetStatus;
use App\Enums\PredictionRoundStatus;
use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\PredictionBet;
use App\Models\PredictionRound;
use App\Models\User;
use App\Support\PredictionTrendRoad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PredictionGameService
{
    public function __construct(
        private readonly MultiWalletService $wallets,
        private readonly WalletLiveRateService $rates,
        private readonly ProvablyFairService $fair,
        private readonly NotificationService $notifications,
        private readonly PredictionTrendDeckService $trendDeck,
        private readonly ReferralCommissionService $referrals,
    ) {}

    /**
     * Advance the shared live round and keep a continuous 12s loop (6s bet + 6s result).
     * Always returns an active betting/settling round and never stays idle.
     */
    public function tick(): ?PredictionRound
    {
        return DB::transaction(function () {
            PredictionRound::query()->orderByDesc('id')->lockForUpdate()->first();

            $round = PredictionRound::query()
                ->whereIn('status', [PredictionRoundStatus::Betting, PredictionRoundStatus::Settling])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $now = now();

            if ($round) {
                if ($round->status === PredictionRoundStatus::Betting && $round->betting_ends_at->lte($now)) {
                    $this->settleRound($round);
                    $round = $round->fresh();
                }

                if ($round && $round->status === PredictionRoundStatus::Settling && $round->round_ends_at->lte($now)) {
                    $round->update(['status' => PredictionRoundStatus::Completed]);
                    $round = null;
                }
            }

            if (! $round) {
                $stillLive = PredictionRound::query()
                    ->whereIn('status', [PredictionRoundStatus::Betting, PredictionRoundStatus::Settling])
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                $round = $stillLive ?: $this->createRound();
            }

            return $round->fresh();
        });
    }

    public function createRound(): PredictionRound
    {
        $betSeconds = PlatformSettings::predictionBettingSeconds();
        $settleSeconds = PlatformSettings::predictionSettleSeconds();
        $serverSeed = $this->fair->generateServerSeed();
        $started = now();

        $lastNumber = (int) PredictionRound::query()->max('round_number');

        return PredictionRound::query()->create([
            'round_number' => $lastNumber + 1,
            'status' => PredictionRoundStatus::Betting,
            'server_seed' => $serverSeed,
            'server_seed_hash' => $this->fair->hashServerSeed($serverSeed),
            'client_seed' => $this->fair->generateClientSeed(),
            'nonce' => $lastNumber + 1,
            'winning_outcome' => null,
            'total_bets' => 0,
            'total_payout' => 0,
            'total_players' => 0,
            'betting_ends_at' => $started->copy()->addSeconds($betSeconds),
            'round_ends_at' => $started->copy()->addSeconds($betSeconds + $settleSeconds),
            'started_at' => $started,
        ]);
    }

    public function placeBet(User $user, int $outcome, float $amount, WalletCurrency|string|null $currency = null): PredictionBet
    {
        if (! in_array($outcome, [1, 2], true)) {
            throw ValidationException::withMessages(['outcome' => 'Choose outcome 1 or 2.']);
        }

        $currency = $this->resolvePlayCurrency($currency, $user);
        $amount = round(max(0, $amount), $currency->decimals());
        $amountUsd = $this->rates->amountToUsd($currency, $amount);
        $minUsd = PlatformSettings::predictionMinBet();
        $maxUsd = PlatformSettings::predictionMaxBet();

        if ($amountUsd < $minUsd || $amountUsd > $maxUsd) {
            $minNative = $this->rates->cryptoAmountForUsd($currency, $minUsd);
            $maxNative = $this->rates->cryptoAmountForUsd($currency, $maxUsd);

            throw ValidationException::withMessages([
                'amount' => 'Bet must be between '.$currency->format($minNative).' and '.$currency->format($maxNative).'.',
            ]);
        }

        $this->tick();

        return DB::transaction(function () use ($user, $outcome, $amount, $currency) {
            $round = PredictionRound::query()
                ->where('status', PredictionRoundStatus::Betting)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $round || ! $round->isBettingOpen()) {
                throw ValidationException::withMessages(['amount' => 'Betting is closed for this round. Wait for the next round.']);
            }

            $round = PredictionRound::query()->whereKey($round->id)->lockForUpdate()->firstOrFail();

            if (! $round->isBettingOpen()) {
                throw ValidationException::withMessages(['amount' => 'Betting is closed for this round. Wait for the next round.']);
            }

            $existing = PredictionBet::query()
                ->where('user_id', $user->id)
                ->where('prediction_round_id', $round->id)
                ->where('status', PredictionBetStatus::Active)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $oldCurrency = $existing->walletCurrency();
                $oldAmount = (float) $existing->amount;
                $sameCurrency = $oldCurrency === $currency;

                if ($sameCurrency) {
                    $diff = $amount - $oldAmount;

                    if ($diff > 0 && $diff > $this->wallets->balance($user, $currency)) {
                        throw ValidationException::withMessages([
                            'amount' => 'Insufficient '.$currency->value.' wallet balance.',
                        ]);
                    }

                    if ($diff > 0) {
                        $this->wallets->debit(
                            $user,
                            $currency,
                            $diff,
                            TransactionType::PredictionBet,
                            'Prediction bet increase #'.$round->round_number,
                            $existing,
                        );
                    } elseif ($diff < 0) {
                        $this->wallets->credit(
                            $user,
                            $currency,
                            abs($diff),
                            TransactionType::PredictionBetRefund,
                            'Prediction bet decrease #'.$round->round_number,
                            $existing,
                        );
                    }
                } else {
                    if ($amount > 0 && $amount > $this->wallets->balance($user, $currency)) {
                        throw ValidationException::withMessages([
                            'amount' => 'Insufficient '.$currency->value.' wallet balance.',
                        ]);
                    }

                    if ($oldAmount > 0) {
                        $this->wallets->credit(
                            $user,
                            $oldCurrency,
                            $oldAmount,
                            TransactionType::PredictionBetRefund,
                            'Prediction bet currency switch #'.$round->round_number,
                            $existing,
                        );
                    }

                    if ($amount > 0) {
                        $this->wallets->debit(
                            $user,
                            $currency,
                            $amount,
                            TransactionType::PredictionBet,
                            'Prediction bet currency switch #'.$round->round_number,
                            $existing,
                        );
                    }
                }

                $payload = [
                    'outcome' => $outcome,
                    'amount' => $amount,
                ];

                if ($this->betsTrackCurrency()) {
                    $payload['currency'] = $currency->value;
                }

                $existing->update($payload);

                $this->recalcRoundTotals($round);

                return $existing->fresh();
            }

            if ($amount > 0 && $amount > $this->wallets->balance($user, $currency)) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient '.$currency->value.' wallet balance.',
                ]);
            }

            $create = [
                'user_id' => $user->id,
                'prediction_round_id' => $round->id,
                'outcome' => $outcome,
                'amount' => $amount,
                'status' => PredictionBetStatus::Active,
            ];

            if ($this->betsTrackCurrency()) {
                $create['currency'] = $currency->value;
            }

            $bet = PredictionBet::query()->create($create);

            if ($amount > 0) {
                $this->wallets->debit(
                    $user,
                    $currency,
                    $amount,
                    TransactionType::PredictionBet,
                    'Prediction bet round #'.$round->round_number.' on '.$outcome,
                    $bet,
                );
            }

            $this->recalcRoundTotals($round);

            return $bet->fresh();
        });
    }

    public function cancelBet(User $user): ?PredictionBet
    {
        $this->tick();

        return DB::transaction(function () use ($user) {
            $round = PredictionRound::query()
                ->where('status', PredictionRoundStatus::Betting)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $round || ! $round->isBettingOpen()) {
                throw ValidationException::withMessages(['bet' => 'Betting is closed. Cannot cancel.']);
            }

            $bet = PredictionBet::query()
                ->where('user_id', $user->id)
                ->where('prediction_round_id', $round->id)
                ->where('status', PredictionBetStatus::Active)
                ->lockForUpdate()
                ->first();

            if (! $bet) {
                return null;
            }

            $amount = (float) $bet->amount;
            $currency = $bet->walletCurrency();
            $bet->update(['status' => PredictionBetStatus::Cancelled]);

            if ($amount > 0) {
                $this->wallets->credit(
                    $user,
                    $currency,
                    $amount,
                    TransactionType::PredictionBetRefund,
                    'Prediction bet cancelled #'.$round->round_number,
                    $bet,
                );
            }

            $this->recalcRoundTotals($round);

            return $bet->fresh();
        });
    }

    public function settleRound(PredictionRound $round): void
    {
        if ($round->status !== PredictionRoundStatus::Betting) {
            return;
        }

        $bets = PredictionBet::query()
            ->with('user')
            ->where('prediction_round_id', $round->id)
            ->where('status', PredictionBetStatus::Active)
            ->lockForUpdate()
            ->get();

        $outcome = $this->trendDeck->nextOutcome();

        if ($bets->isEmpty()) {
            $round->update([
                'status' => PredictionRoundStatus::Settling,
                'winning_outcome' => $outcome,
                'total_payout' => 0,
                'settled_at' => now(),
            ]);

            return;
        }

        $multiplier = PlatformSettings::predictionPayoutMultiplier();
        $totalPayout = 0.0;

        foreach ($bets as $bet) {
            $currency = $bet->walletCurrency();
            $stake = (float) $bet->amount;

            if ((int) $bet->outcome === $outcome) {
                $payout = round($stake * $multiplier, $currency->decimals());
                $bet->update([
                    'status' => PredictionBetStatus::Won,
                    'payout' => $payout,
                ]);

                if ($payout > 0) {
                    $this->wallets->credit(
                        $bet->user,
                        $currency,
                        $payout,
                        TransactionType::PredictionWin,
                        'Prediction win round #'.$round->round_number.' ('.$multiplier.'x)',
                        $bet,
                    );

                    $totalPayout += $this->rates->amountToUsd($currency, $payout);

                    $profitNative = max(0, round($payout - $stake, $currency->decimals()));
                    $profitUsd = $this->rates->amountToUsd($currency, $profitNative);
                    if ($profitUsd > 0) {
                        $this->referrals->creditForPredictionWin($bet->user, $profitUsd, $bet, now());
                    }

                    $this->notifications->notifyUser(
                        $bet->user,
                        'prediction',
                        'Prediction Win',
                        'You won '.$currency->format($payout).' on round #'.$round->round_number.'.',
                        ['url' => route('user.prediction.index')],
                    );
                } else {
                    $this->notifications->notifyUser(
                        $bet->user,
                        'prediction',
                        'Prediction Result',
                        'You called it right on round #'.$round->round_number.' ('.$multiplier.'x). $0 play — no wallet credit.',
                        ['url' => route('user.prediction.index')],
                    );
                }
            } else {
                $bet->update([
                    'status' => PredictionBetStatus::Lost,
                    'payout' => 0,
                ]);

                $this->notifications->notifyUser(
                    $bet->user,
                    'prediction',
                    'Prediction Loss',
                    'You lost '.$currency->format($stake).' on round #'.$round->round_number.'. Result: '.$outcome.' won.',
                    ['url' => route('user.prediction.index')],
                );
            }
        }

        $round->update([
            'status' => PredictionRoundStatus::Settling,
            'winning_outcome' => $outcome,
            'total_payout' => $totalPayout,
            'settled_at' => now(),
        ]);
    }

    public function stateFor(?User $user = null): array
    {
        $round = $this->tick();
        $multiplier = PlatformSettings::predictionPayoutMultiplier();
        $now = now();
        $library = $this->trendDeck->activeLibrarySummary();

        $userBet = null;
        $pool1 = 0.0;
        $pool2 = 0.0;
        $players = [];

        if ($round && $user) {
            $userBet = PredictionBet::query()
                ->where('user_id', $user->id)
                ->where('prediction_round_id', $round->id)
                ->whereIn('status', [PredictionBetStatus::Active, PredictionBetStatus::Won, PredictionBetStatus::Lost])
                ->first();
        }

        if ($round) {
            $poolStatuses = $round->status === PredictionRoundStatus::Betting
                ? [PredictionBetStatus::Active]
                : [PredictionBetStatus::Active, PredictionBetStatus::Won, PredictionBetStatus::Lost];

            $poolBets = PredictionBet::query()
                ->where('prediction_round_id', $round->id)
                ->whereIn('status', $poolStatuses)
                ->get(['outcome', 'amount', 'currency']);

            foreach ($poolBets as $bet) {
                $usd = $this->betAmountUsd($bet);
                if ((int) $bet->outcome === 1) {
                    $pool1 += $usd;
                } elseif ((int) $bet->outcome === 2) {
                    $pool2 += $usd;
                }
            }

            $players = PredictionBet::query()
                ->with(['user:id,name'])
                ->where('prediction_round_id', $round->id)
                ->whereIn('status', $poolStatuses)
                ->orderByDesc('amount')
                ->limit(24)
                ->get()
                ->map(function (PredictionBet $bet) {
                    $name = trim((string) ($bet->user?->name ?? 'Player'));
                    $currency = $bet->walletCurrency();

                    return [
                        'name' => $name !== '' ? $name : 'Player',
                        'outcome' => (int) $bet->outcome,
                        'amount' => round((float) $bet->amount, $currency->decimals()),
                        'currency' => $currency->value,
                        'symbol' => $currency->symbol(),
                        'formatted' => $currency->format((float) $bet->amount),
                        'status' => $bet->status->value,
                    ];
                })
                ->values()
                ->all();
        }

        $totalPool = $pool1 + $pool2;
        $pct1 = $totalPool > 0 ? round(($pool1 / $totalPool) * 100) : 50;
        $pct2 = 100 - $pct1;
        $hasBets = $totalPool > 0 || ($round && (float) $round->total_bets > 0);

        $history50 = PredictionRound::query()
            ->whereNotNull('winning_outcome')
            ->where('total_bets', '>', 0)
            ->whereIn('status', [PredictionRoundStatus::Completed, PredictionRoundStatus::Settling])
            ->orderByDesc('id')
            ->limit(80)
            ->pluck('winning_outcome')
            ->reverse()
            ->values();

        $myBets = collect();
        if ($user) {
            $myBets = PredictionBet::query()
                ->with(['round:id,round_number'])
                ->where('user_id', $user->id)
                ->whereIn('status', [
                    PredictionBetStatus::Active,
                    PredictionBetStatus::Won,
                    PredictionBetStatus::Lost,
                ])
                ->orderByDesc('id')
                ->limit(20)
                ->get();
        }

        $revealSeed = $round && in_array($round->status, [PredictionRoundStatus::Settling, PredictionRoundStatus::Completed], true);

        $playCurrency = $user
            ? $this->resolvePlayCurrency(
                $userBet?->walletCurrency()->value ?? $user->preferredWalletCurrency()->value,
                $user
            )
            : WalletCurrency::Usdt;

        $minUsd = PlatformSettings::predictionMinBet();
        $maxUsd = PlatformSettings::predictionMaxBet();
        $currencyOptions = $this->currencyOptionsFor($user, $minUsd, $maxUsd);
        $walletBalance = $user ? $this->wallets->balance($user, $playCurrency) : 0.0;

        return [
            'idle' => false,
            'has_bets' => (bool) $hasBets,
            'round' => [
                'id' => $round->id,
                'number' => $round->round_number,
                'status' => $round->status->value,
                'server_seed_hash' => $round->server_seed_hash,
                'server_seed' => $revealSeed ? $round->server_seed : null,
                'client_seed' => $round->client_seed,
                'nonce' => $round->nonce,
                'winning_outcome' => $round->winning_outcome,
                'betting_ends_at' => $round->betting_ends_at->toIso8601String(),
                'round_ends_at' => $round->round_ends_at->toIso8601String(),
                'betting_seconds_left' => max(0, $round->betting_ends_at->getTimestamp() - $now->getTimestamp()),
                'round_seconds_left' => max(0, $round->round_ends_at->getTimestamp() - $now->getTimestamp()),
                'server_now' => $now->toIso8601String(),
            ],
            'config' => [
                'min_bet' => $this->rates->cryptoAmountForUsd($playCurrency, $minUsd),
                'max_bet' => $this->rates->cryptoAmountForUsd($playCurrency, $maxUsd),
                'min_bet_usd' => $minUsd,
                'max_bet_usd' => $maxUsd,
                'payout' => $multiplier,
                'win_chance' => 50,
                'betting_seconds' => PlatformSettings::predictionBettingSeconds(),
                'settle_seconds' => PlatformSettings::predictionSettleSeconds(),
                'rtp' => 96,
                'house_edge' => 4,
                'outcome_source' => $this->trendDeck->outcomeSourceLabel(),
                'library' => $library,
            ],
            'stats' => [
                'total_pool' => $round ? round((float) $round->total_bets, 2) : 0,
                'total_players' => $round ? (int) $round->total_players : 0,
                'pool_1' => round($pool1, 2),
                'pool_2' => round($pool2, 2),
                'pct_1' => $pct1,
                'pct_2' => $pct2,
            ],
            'players' => $players,
            'user_bet' => $userBet ? [
                'outcome' => (int) $userBet->outcome,
                'amount' => (float) $userBet->amount,
                'currency' => $userBet->walletCurrency()->value,
                'symbol' => $userBet->walletCurrency()->symbol(),
                'formatted' => $userBet->walletCurrency()->format((float) $userBet->amount),
                'status' => $userBet->status->value,
                'payout' => $userBet->payout !== null ? (float) $userBet->payout : null,
                'payout_formatted' => $userBet->payout !== null
                    ? $userBet->walletCurrency()->format((float) $userBet->payout)
                    : null,
            ] : null,
            'play_currency' => $playCurrency->value,
            'play_symbol' => $playCurrency->symbol(),
            'currencies' => $currencyOptions,
            'wallet' => $walletBalance,
            'wallet_formatted' => $playCurrency->format($walletBalance),
            'my_bets' => $myBets->map(function (PredictionBet $bet) use ($multiplier) {
                $status = $bet->status->value;
                $currency = $bet->walletCurrency();
                $amount = round((float) $bet->amount, $currency->decimals());
                $payoutAmount = $bet->payout !== null ? round((float) $bet->payout, $currency->decimals()) : null;
                $profit = match ($status) {
                    'won' => round(($payoutAmount ?? ($amount * $multiplier)) - $amount, $currency->decimals()),
                    'lost' => 0.0,
                    default => null,
                };
                $resultLabel = match ($status) {
                    'won' => '+'.$currency->format($profit ?? 0),
                    'lost' => $currency->format(0),
                    default => 'Pending',
                };

                return [
                    'round' => $bet->round?->round_number,
                    'amount' => $amount,
                    'currency' => $currency->value,
                    'symbol' => $currency->symbol(),
                    'formatted' => $currency->format($amount),
                    'outcome' => (int) $bet->outcome,
                    'status' => $status,
                    'payout_mult' => $status === 'lost' ? 0.0 : round((float) $multiplier, 2),
                    'profit' => $profit,
                    'result' => $resultLabel,
                ];
            })->values(),
            'history' => $history50,
            'trend_road' => PredictionTrendRoad::fromHistory($history50),
        ];
    }

    private function recalcRoundTotals(PredictionRound $round): void
    {
        $active = PredictionBet::query()
            ->where('prediction_round_id', $round->id)
            ->where('status', PredictionBetStatus::Active)
            ->get(['amount', 'currency', 'user_id']);

        $totalUsd = 0.0;
        foreach ($active as $bet) {
            $totalUsd += $this->betAmountUsd($bet);
        }

        $round->update([
            'total_bets' => round($totalUsd, 2),
            'total_players' => $active->pluck('user_id')->unique()->count(),
        ]);
    }

    private function resolvePlayCurrency(WalletCurrency|string|null $currency, ?User $user = null): WalletCurrency
    {
        if ($currency instanceof WalletCurrency) {
            return $currency;
        }

        $resolved = WalletCurrency::tryNormalize(is_string($currency) ? $currency : null);

        if ($resolved) {
            return $resolved;
        }

        return $user?->preferredWalletCurrency() ?? WalletCurrency::Usdt;
    }

    private function betAmountUsd(PredictionBet $bet): float
    {
        return $this->rates->amountToUsd($bet->walletCurrency(), (float) $bet->amount);
    }

    private function betsTrackCurrency(): bool
    {
        return Schema::hasColumn('prediction_bets', 'currency');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function currencyOptionsFor(?User $user, float $minUsd, float $maxUsd): array
    {
        $balances = $user ? $this->wallets->balances($user) : collect();
        $preferred = $user?->preferredWalletCurrency()->value;
        $options = [];

        foreach (WalletCurrency::pickerOrder() as $currency) {
            $balance = (float) ($balances[$currency->value] ?? 0);
            $minNative = $this->rates->cryptoAmountForUsd($currency, $minUsd);
            $maxNative = $this->rates->cryptoAmountForUsd($currency, $maxUsd);

            $options[] = [
                'code' => $currency->value,
                'label' => $currency->label(),
                'symbol' => $currency->symbol(),
                'icon' => $currency->iconUrl(),
                'decimals' => $currency->decimals(),
                'balance' => $balance,
                'formatted' => $currency->format($balance),
                'min_bet' => $minNative,
                'max_bet' => $maxNative,
                'step' => $currency->decimals() >= 8 ? '0.00000001' : ($currency->decimals() >= 6 ? '0.000001' : '0.01'),
                'selected' => $preferred === $currency->value,
            ];
        }

        return $options;
    }
}


