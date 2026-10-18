<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\RpsvInvite;
use App\Models\RpsvMatch;
use App\Models\RpsvQueueEntry;
use App\Models\RpsvRound;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RpsvGameService
{
    public const MULTIPLIER = 1.90;

    public const FEE_RATE = 0.05;

    public const MAX_ROUNDS = 3;

    /** First player to this many round wins takes the match (draws do not count). */
    public const WINS_NEEDED = 2;

    public const REVEAL_SECONDS = 2;

    public const RESULT_SECONDS = 2;

    public const TOAST_SECONDS = 3;

    public const INVITE_TTL_MINUTES = 10;

    /** @var list<float> */
    public const STAKE_PRESETS_USD = [
        0.05, 0.08, 0.11, 0.15, 0.20,
        0.30, 0.50, 0.80, 1, 2,
        3, 5, 8, 10, 15,
        20, 30, 50, 100, 1000,
    ];

    /** Default Naira stake options (client). */
    /** @var list<float> */
    public const STAKE_PRESETS_NGN = [
        25, 50, 100, 200, 300, 500, 800, 1000, 1500, 2000,
        3000, 4000, 8000, 16000, 32000, 64000, 128000, 256000, 500000, 1000000,
    ];

    /** @deprecated Use stakePresetsFor() — kept for callers expecting a flat list (USD). */
    public const STAKE_PRESETS = self::STAKE_PRESETS_USD;

    public function __construct(
        private readonly RpsvWalletService $wallet,
        private readonly NotificationService $notifications,
        private readonly WalletLiveRateService $rates,
    ) {}

    /**
     * @return list<float>
     */
    public function stakePresets(): array
    {
        return self::STAKE_PRESETS_USD;
    }

    /**
     * Lobby stake chips: fiat uses NGN presets; all crypto uses USD presets
     * (native crypto is calculated from live rates when the stake is locked).
     *
     * @return list<float>
     */
    public function stakePresetsFor(string $currency): array
    {
        $currency = strtoupper(trim($currency));

        return $currency === 'NGN' ? self::STAKE_PRESETS_NGN : self::STAKE_PRESETS_USD;
    }

    /**
     * @return array{USD: list<float>, NGN: list<float>}
     */
    public function allStakePresets(): array
    {
        return [
            'USD' => self::STAKE_PRESETS_USD,
            'NGN' => self::STAKE_PRESETS_NGN,
        ];
    }

    public function joinQuickMatch(User $user, string $currency, float $stake): RpsvMatch
    {
        $currency = $this->normalizeCurrency($currency);
        $resolved = $this->resolveStakeForPlay($currency, $stake);
        $nativeStake = $resolved['native'];
        $queueKey = $resolved['queue_key'];

        return DB::transaction(function () use ($user, $currency, $nativeStake, $queueKey) {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $existingQueue = RpsvQueueEntry::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if ($existingQueue) {
                $oldMatch = $existingQueue->match_id
                    ? RpsvMatch::query()->whereKey($existingQueue->match_id)->lockForUpdate()->first()
                    : null;
                if ($oldMatch && in_array($oldMatch->status, ['waiting', 'queued'], true) && ! $oldMatch->player2_id) {
                    $this->wallet->credit(
                        $user,
                        $oldMatch->currency,
                        (float) $oldMatch->stake,
                        TransactionType::RpsvStakeRefund,
                        'RPSV requeue refund #'.$oldMatch->id,
                        $oldMatch
                    );
                    $this->notifyRefund($user, $oldMatch->currency, (float) $oldMatch->stake, $oldMatch, 'Previous queue cancelled — stake refunded.');
                    $oldMatch->update(['status' => 'cancelled', 'completed_at' => now()]);
                }
                $existingQueue->delete();
            }

            $this->assertNotInActiveMatch($user->id);

            $match = $this->createShellMatch($user, 'quick', $currency, $nativeStake);
            $this->wallet->debit($user, $currency, $nativeStake, TransactionType::RpsvStake, 'RPSV quick-match stake #'.$match->id, $match);
            $this->notifyStake($user, $currency, $nativeStake, $match, 'Quick Match stake locked while searching for an opponent.');

            RpsvQueueEntry::query()->create([
                'user_id' => $user->id,
                'currency' => $currency,
                'stake' => $queueKey,
                'match_id' => $match->id,
                'joined_at' => now(),
            ]);

            $match->update(['status' => 'queued']);

            $this->tryPairQueue($currency, $queueKey);

            return $match->fresh(['player1', 'player2', 'rounds']);
        });
    }

    public function cancelQueue(User $user): void
    {
        DB::transaction(function () use ($user) {
            $entry = RpsvQueueEntry::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $entry) {
                return;
            }

            $match = $entry->match_id
                ? RpsvMatch::query()->whereKey($entry->match_id)->lockForUpdate()->first()
                : null;

            if ($match && in_array($match->status, ['waiting', 'queued'], true) && ! $match->player2_id) {
                $this->wallet->credit(
                    $user,
                    $match->currency,
                    (float) $match->stake,
                    TransactionType::RpsvStakeRefund,
                    'RPSV queue cancel refund #'.$match->id,
                    $match
                );
                $this->notifyRefund($user, $match->currency, (float) $match->stake, $match, 'Queue cancelled — stake refunded to your wallet.');
                $match->update(['status' => 'cancelled', 'completed_at' => now()]);
            }

            $entry->delete();
        });
    }

    public function sendInvite(User $sender, string $username, string $currency, float $stake): RpsvInvite
    {
        $this->expireInvites();

        $currency = $this->normalizeCurrency($currency);
        $resolved = $this->resolveStakeForPlay($currency, $stake);
        $nativeStake = $resolved['native'];
        $receiver = $this->findPlayer($username);

        if (! $receiver) {
            throw ValidationException::withMessages(['username' => 'Player not found. Use their exact name, member ID, or email.']);
        }
        if ($receiver->id === $sender->id) {
            throw ValidationException::withMessages(['username' => 'You cannot invite yourself.']);
        }

        $invite = DB::transaction(function () use ($sender, $receiver, $currency, $nativeStake) {
            $sender = User::query()->whereKey($sender->id)->lockForUpdate()->firstOrFail();
            $this->assertNotInActiveMatch($sender->id);

            $match = $this->createShellMatch($sender, 'invite', $currency, $nativeStake);
            $this->wallet->debit($sender, $currency, $nativeStake, TransactionType::RpsvStake, 'RPSV invite stake #'.$match->id, $match);

            $invite = RpsvInvite::query()->create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'currency' => $currency,
                'stake' => $nativeStake,
                'status' => 'pending',
                'match_id' => $match->id,
                'expires_at' => now()->addMinutes(self::INVITE_TTL_MINUTES),
            ]);

            $match->update(['status' => 'waiting', 'mode' => 'invite']);

            return $invite->fresh(['sender', 'receiver', 'match']);
        });

        $this->notifyStake($sender->fresh(), $currency, $nativeStake, $invite->match ?? $invite->match()->firstOrFail(), 'Invite stake locked. Waiting for '.$receiver->name.' to accept.');
        try {
            $this->notifications->notifyUser(
                $receiver,
                'rpsv',
                'RPSV Challenge',
                $sender->name.' challenged you for '.$this->money($currency, $nativeStake).'. Open RPSV → My Invitations to accept.',
                ['url' => route('user.rpsv.index', ['tab' => 'mine'])],
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return $invite;
    }

    public function acceptInvite(User $receiver, RpsvInvite $invite): RpsvMatch
    {
        $match = DB::transaction(function () use ($receiver, $invite) {
            $invite = RpsvInvite::query()->whereKey($invite->id)->lockForUpdate()->firstOrFail();
            if ($invite->receiver_id !== $receiver->id) {
                abort(403);
            }
            if ($invite->status !== 'pending' || $this->inviteHasExpired($invite)) {
                throw ValidationException::withMessages(['invite' => 'Invite is no longer valid.']);
            }

            $receiver = User::query()->whereKey($receiver->id)->lockForUpdate()->firstOrFail();
            $this->assertNotInActiveMatch($receiver->id);

            $match = RpsvMatch::query()->whereKey($invite->match_id)->lockForUpdate()->firstOrFail();
            $this->wallet->debit(
                $receiver,
                $match->currency,
                (float) $match->stake,
                TransactionType::RpsvStake,
                'RPSV invite accept stake #'.$match->id,
                $match
            );

            $invite->update(['status' => 'accepted']);
            $this->startMatch($match, $receiver->id);

            return $match->fresh(['player1', 'player2', 'rounds']);
        });

        // Notifications after commit — keeps accept + redirect fast for both players.
        try {
            $this->notifyStake(
                $receiver->fresh(),
                $match->currency,
                (float) $match->stake,
                $match,
                'Invite accepted — stake locked. Press Start when both players are ready.'
            );
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $sender = $match->player1;
            if ($sender) {
                $this->notifications->notifyUser(
                    $sender,
                    'rpsv',
                    'Invite Accepted',
                    ($receiver->name ?: 'Your opponent').' accepted your RPSV challenge. Open the match and press Start.',
                    ['url' => route('user.rpsv.match', $match)],
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $match;
    }

    public function declineInvite(User $receiver, RpsvInvite $invite): void
    {
        DB::transaction(function () use ($receiver, $invite) {
            $invite = RpsvInvite::query()->whereKey($invite->id)->lockForUpdate()->firstOrFail();
            if ($invite->receiver_id !== $receiver->id) {
                abort(403);
            }
            if ($invite->status !== 'pending') {
                return;
            }

            $invite->update(['status' => 'declined']);
            $match = RpsvMatch::query()->whereKey($invite->match_id)->lockForUpdate()->first();
            if ($match && in_array($match->status, ['waiting', 'queued'], true)) {
                $sender = User::query()->whereKey($invite->sender_id)->lockForUpdate()->firstOrFail();
                $this->wallet->credit(
                    $sender,
                    $match->currency,
                    (float) $match->stake,
                    TransactionType::RpsvStakeRefund,
                    'RPSV invite declined refund #'.$match->id,
                    $match
                );
                $this->notifyRefund(
                    $sender,
                    $match->currency,
                    (float) $match->stake,
                    $match,
                    'Invite was declined — stake refunded to your wallet.'
                );
                $match->update(['status' => 'cancelled', 'completed_at' => now()]);
            }
        });
    }

    public function cancelInvite(User $sender, RpsvInvite $invite): void
    {
        DB::transaction(function () use ($sender, $invite) {
            $invite = RpsvInvite::query()->whereKey($invite->id)->lockForUpdate()->firstOrFail();
            if ($invite->sender_id !== $sender->id) {
                abort(403);
            }
            if ($invite->status !== 'pending') {
                return;
            }

            $invite->update(['status' => 'cancelled']);
            $match = RpsvMatch::query()->whereKey($invite->match_id)->lockForUpdate()->first();
            if ($match && in_array($match->status, ['waiting', 'queued'], true)) {
                $lockedSender = User::query()->whereKey($invite->sender_id)->lockForUpdate()->firstOrFail();
                $this->wallet->credit(
                    $lockedSender,
                    $match->currency,
                    (float) $match->stake,
                    TransactionType::RpsvStakeRefund,
                    'RPSV invite cancel refund #'.$match->id,
                    $match
                );
                $this->notifyRefund(
                    $lockedSender,
                    $match->currency,
                    (float) $match->stake,
                    $match,
                    'Invite cancelled — stake refunded to your wallet.'
                );
                $match->update(['status' => 'cancelled', 'completed_at' => now()]);
            }
        });
    }

    public function markReady(User $user, RpsvMatch $match): RpsvMatch
    {
        return DB::transaction(function () use ($user, $match) {
            $match = RpsvMatch::query()->whereKey($match->id)->lockForUpdate()->firstOrFail();
            if (! $match->isPlayer($user->id) || $match->status !== 'active' || $match->phase !== 'ready') {
                throw ValidationException::withMessages(['match' => 'Waiting for both players to press Start.']);
            }

            if ($match->player1_id === $user->id) {
                $match->player1_ready = true;
            } else {
                $match->player2_ready = true;
            }
            $match->save();

            if ($match->player1_ready && $match->player2_ready) {
                $this->beginIdlePhase($match);
            }

            return $match->fresh(['player1', 'player2', 'rounds']);
        });
    }

    public function submitMove(User $user, RpsvMatch $match, string $move): RpsvMatch
    {
        $move = strtolower($move);
        if (! in_array($move, ['rock', 'paper', 'scissors'], true)) {
            throw ValidationException::withMessages(['move' => 'Choose rock, paper, or scissors.']);
        }

        return DB::transaction(function () use ($user, $match, $move) {
            $match = RpsvMatch::query()->whereKey($match->id)->lockForUpdate()->firstOrFail();
            if (! $match->isPlayer($user->id) || $match->status !== 'active' || ! in_array($match->phase, ['idle', 'select'], true)) {
                throw ValidationException::withMessages(['move' => 'Press Start first, then make your move when it is your turn.']);
            }

            $round = RpsvRound::query()
                ->where('rpsv_match_id', $match->id)
                ->where('round_number', $match->current_round)
                ->lockForUpdate()
                ->firstOrFail();

            $isP1 = $match->player1_id === $user->id;

            // Strict turn order: Player 1 chooses first, then Player 2.
            if ($isP1) {
                if ($round->player1_move) {
                    throw ValidationException::withMessages(['move' => 'You already locked your move. Waiting for Player 2.']);
                }
                if ($round->player2_move) {
                    throw ValidationException::withMessages(['move' => 'Invalid round state.']);
                }
                $round->player1_move = $move;
                $round->save();
                $this->beginWaitingForPlayer2($match, $round);
            } else {
                if (! $round->player1_move) {
                    throw ValidationException::withMessages(['move' => 'Wait for Player 1 to make their choice first.']);
                }
                if ($round->player2_move) {
                    throw ValidationException::withMessages(['move' => 'Move already locked.']);
                }
                $round->player2_move = $move;
                $round->save();
                $this->enterRevealPhase($match, $round->fresh());
            }

            return $match->fresh(['player1', 'player2', 'rounds']);
        });
    }

    public function forfeit(User $user, RpsvMatch $match): RpsvMatch
    {
        return DB::transaction(function () use ($user, $match) {
            $match = RpsvMatch::query()->whereKey($match->id)->lockForUpdate()->firstOrFail();
            if (! $match->isPlayer($user->id) || ! in_array($match->status, ['active', 'revealing', 'result'], true)) {
                throw ValidationException::withMessages(['match' => 'Cannot forfeit now.']);
            }
            // Ready / idle / select / reveal / result are all forfeitable while status is active|revealing|result.

            $winnerId = $match->opponentId($user->id);
            $match->update(['forfeit_by' => $user->id]);
            $this->completeMatch($match, $winnerId);

            return $match->fresh(['player1', 'player2', 'rounds', 'winner']);
        });
    }

    public function tick(): void
    {
        $this->expireInvites();
        $this->pairAllQueues();
        $this->advanceMatches();
    }

    /**
     * @return array<string, mixed>
     */
    public function syncMatch(RpsvMatch $match): RpsvMatch
    {
        for ($i = 0; $i < 8; $i++) {
            $advanced = false;
            DB::transaction(function () use ($match, &$advanced) {
                $locked = RpsvMatch::query()->whereKey($match->id)->lockForUpdate()->first();
                $advanced = $locked ? $this->processExpiredPhase($locked) : false;
            });

            $match = $match->fresh(['player1', 'player2', 'rounds', 'winner']) ?? $match;
            if (! $advanced) {
                break;
            }
        }

        return $match;
    }

    public function stateFor(User $user, RpsvMatch $match): array
    {
        abort_unless($match->isPlayer($user->id), 403);

        if (in_array($match->status, ['active', 'revealing', 'result'], true)) {
            $match = $this->syncMatch($match);
        }

        $match->loadMissing(['player1:id,name,member_id', 'player2:id,name,member_id', 'rounds', 'winner:id,name']);

        $isP1 = $match->player1_id === $user->id;
        $you = $isP1 ? $match->player1 : $match->player2;
        $opp = $isP1 ? $match->player2 : $match->player1;
        $yourScore = $isP1 ? $match->player1_score : $match->player2_score;
        $oppScore = $isP1 ? $match->player2_score : $match->player1_score;

        $current = $match->rounds->firstWhere('round_number', $match->current_round);
        $yourMove = null;
        $oppMoveHidden = true;
        $oppMove = null;
        $oppLocked = false;

        if ($current) {
            $yourMove = $isP1 ? $current->player1_move : $current->player2_move;
            $rawOpp = $isP1 ? $current->player2_move : $current->player1_move;
            $oppLocked = (bool) $rawOpp;
            if (in_array($match->phase, ['reveal', 'result'], true) || in_array($match->status, ['completed', 'forfeit'], true)) {
                $oppMoveHidden = false;
                $oppMove = $rawOpp;
            } else {
                $oppMoveHidden = true;
                $oppMove = $rawOpp ? 'hidden' : null;
            }
        }

        $secondsLeft = 0;
        if ($match->phase_ends_at && $match->phase_ends_at->isFuture()) {
            $secondsLeft = max(0, (int) ceil(now()->floatDiffInSeconds($match->phase_ends_at)));
        } elseif ($match->phase === 'select' && $match->phase_ends_at && ! $match->phase_ends_at->isFuture()) {
            $secondsLeft = 0;
        }

        $history = $match->rounds
            ->filter(fn (RpsvRound $r) => ($r->player1_move || $r->player2_move) && in_array($r->status, ['locked', 'revealed', 'done'], true))
            ->sortBy('round_number')
            ->values()
            ->map(function (RpsvRound $r) use ($isP1, $match) {
                $p1 = $r->player1_move;
                $p2 = $r->player2_move;
                $youId = $isP1 ? $match->player1_id : $match->player2_id;

                return [
                    'round' => $r->round_number,
                    'you' => $isP1 ? $p1 : $p2,
                    'opponent' => $isP1 ? $p2 : $p1,
                    'winner_id' => $r->winner_id,
                    'you_won' => $r->winner_id !== null && $r->winner_id === $youId,
                    'draw' => ($r->player1_move && $r->player2_move && $r->winner_id === null)
                        || ((! $r->player1_move && ! $r->player2_move) && $r->winner_id === null),
                    'timeout' => (bool) (($p1 && ! $p2) || ($p2 && ! $p1)),
                ];
            });

        $youReady = $isP1 ? (bool) $match->player1_ready : (bool) $match->player2_ready;
        $oppReady = $isP1 ? (bool) $match->player2_ready : (bool) $match->player1_ready;

        $waitingForP1 = $current && ! $current->player1_move;
        $waitingForP2 = $current && $current->player1_move && ! $current->player2_move;
        $yourTurn = $match->status === 'active'
            && in_array($match->phase, ['idle', 'select'], true)
            && (($isP1 && $waitingForP1) || (! $isP1 && $waitingForP2));

        $turnPrompt = null;
        if ($match->status === 'active' && in_array($match->phase, ['idle', 'select'], true)) {
            if ($waitingForP1) {
                $turnPrompt = $isP1
                    ? 'Player 1, make your choice.'
                    : 'Waiting for Player 1 to choose…';
            } elseif ($waitingForP2) {
                $turnPrompt = $isP1
                    ? 'Waiting for Player 2 to choose…'
                    : 'Player 2, make your choice.';
            }
        }

        return [
            'id' => $match->id,
            'code' => $match->match_code,
            'mode' => $match->mode,
            'currency' => $match->currency,
            'stake' => (float) $match->stake,
            'prize_pool' => (float) $match->prize_pool,
            'platform_fee' => (float) $match->platform_fee,
            'winner_payout' => (float) $match->winner_payout,
            'status' => $match->status,
            'phase' => $match->phase,
            'seconds_left' => $secondsLeft,
            'wins_needed' => self::WINS_NEEDED,
            'current_round' => (int) $match->current_round,
            'max_rounds' => (int) ($match->max_rounds ?: self::MAX_ROUNDS),
            'turn' => $waitingForP1 ? 'player1' : ($waitingForP2 ? 'player2' : null),
            'your_turn' => $yourTurn,
            'turn_prompt' => $turnPrompt,
            'you' => [
                'id' => $you?->id,
                'name' => $you?->name,
                'score' => (int) $yourScore,
                'move' => $yourMove,
                'ready' => $youReady,
                'seat' => $isP1 ? 1 : 2,
            ],
            'opponent' => [
                'id' => $opp?->id,
                'name' => $opp?->name ?? 'Waiting…',
                'score' => (int) $oppScore,
                'move' => $oppMove,
                'hidden' => $oppMoveHidden,
                'locked' => $oppLocked,
                'ready' => $oppReady,
                'seat' => $isP1 ? 2 : 1,
            ],
            'you_ready' => $youReady,
            'opponent_ready' => $oppReady,
            'can_ready' => $match->status === 'active' && $match->phase === 'ready' && ! $youReady,
            'can_move' => $yourTurn,
            'winner_id' => $match->winner_id,
            'history' => $history,
            'banner' => $this->bannerFor($match, $user->id, $current),
            'round_outcome' => $this->roundOutcome($match, $user->id, $current),
            'match_result' => $this->matchResultFor($match, $user->id),
            'toast' => $this->toastFor($match, $user->id, $current),
            'wallet' => $this->wallet->balance($user, $match->currency),
            'usd_balance' => $this->wallet->balance($user, 'USD'),
            'ngn_balance' => $this->wallet->balance($user, 'NGN'),
            'unread_notifications' => $user->userNotifications()->whereNull('read_at')->count(),
        ];
    }

    private function tryPairQueue(string $currency, float $stake): void
    {
        $entries = RpsvQueueEntry::query()
            ->where('currency', $currency)
            ->where('stake', $stake)
            ->orderBy('joined_at')
            ->lockForUpdate()
            ->limit(2)
            ->get();

        if ($entries->count() < 2) {
            return;
        }

        $a = $entries[0];
        $b = $entries[1];
        $matchA = RpsvMatch::query()->whereKey($a->match_id)->lockForUpdate()->first();
        $matchB = RpsvMatch::query()->whereKey($b->match_id)->lockForUpdate()->first();

        if (! $matchA || ! $matchB) {
            return;
        }

        // Keep match A, refund/cancel match B shell and attach player2
        $this->wallet->credit(
            User::query()->whereKey($b->user_id)->lockForUpdate()->firstOrFail(),
            $matchB->currency,
            (float) $matchB->stake,
            TransactionType::RpsvStakeRefund,
            'RPSV rematch merge refund #'.$matchB->id,
            $matchB
        );
        // Immediately re-debit into match A pot accounting: player B stake already refunded, debit for A
        $this->wallet->debit(
            User::query()->whereKey($b->user_id)->firstOrFail(),
            $matchA->currency,
            (float) $matchA->stake,
            TransactionType::RpsvStake,
            'RPSV matched stake #'.$matchA->id,
            $matchA
        );

        $matchB->update(['status' => 'cancelled', 'completed_at' => now()]);
        RpsvQueueEntry::query()->whereIn('id', [$a->id, $b->id])->delete();
        $this->startMatch($matchA, (int) $b->user_id);
    }

    private function pairAllQueues(): void
    {
        $groups = RpsvQueueEntry::query()
            ->select('currency', 'stake')
            ->groupBy('currency', 'stake')
            ->get();

        foreach ($groups as $group) {
            DB::transaction(function () use ($group) {
                $this->tryPairQueue($group->currency, (float) $group->stake);
            });
        }
    }

    private function startMatch(RpsvMatch $match, int $player2Id): void
    {
        $match->update([
            'player2_id' => $player2Id,
            'status' => 'active',
            'phase' => 'ready',
            'player1_ready' => false,
            'player2_ready' => false,
            'current_round' => 1,
            'started_at' => now(),
            'phase_ends_at' => null,
        ]);

        RpsvRound::query()->firstOrCreate(
            ['rpsv_match_id' => $match->id, 'round_number' => 1],
            ['status' => 'open']
        );
    }

    private function beginIdlePhase(RpsvMatch $match): void
    {
        $round = RpsvRound::query()
            ->where('rpsv_match_id', $match->id)
            ->where('round_number', max(1, (int) $match->current_round))
            ->first();

        if (! $round) {
            RpsvRound::query()->create([
                'rpsv_match_id' => $match->id,
                'round_number' => max(1, (int) $match->current_round),
                'status' => 'open',
            ]);
        }

        $match->update([
            'status' => 'active',
            'phase' => 'idle',
            'phase_ends_at' => null,
        ]);
    }

    private function beginWaitingForPlayer2(RpsvMatch $match, RpsvRound $round): void
    {
        $round->update([
            'status' => 'open',
            'select_ends_at' => null,
        ]);

        $match->update([
            'status' => 'active',
            'phase' => 'select',
            'phase_ends_at' => null,
        ]);
    }

    private function enterRevealPhase(RpsvMatch $match, RpsvRound $round): void
    {
        $winnerId = $this->roundWinnerId($match, $round->player1_move, $round->player2_move);
        if ($winnerId === $match->player1_id) {
            $match->player1_score++;
        } elseif ($winnerId === $match->player2_id) {
            $match->player2_score++;
        }

        $round->update([
            'status' => 'locked',
            'winner_id' => $winnerId,
            'reveal_ends_at' => now()->addSeconds(self::REVEAL_SECONDS),
        ]);
        $match->update([
            'status' => 'revealing',
            'phase' => 'reveal',
            'phase_ends_at' => now()->addSeconds(self::REVEAL_SECONDS),
            'player1_score' => $match->player1_score,
            'player2_score' => $match->player2_score,
        ]);
    }

    private function advanceMatches(): void
    {
        $matches = RpsvMatch::query()
            ->whereIn('status', ['active', 'revealing', 'result'])
            ->whereNotNull('phase_ends_at')
            ->where('phase_ends_at', '<=', now())
            ->orderBy('id')
            ->get();

        foreach ($matches as $match) {
            $this->syncMatch($match);
        }
    }

    private function processExpiredPhase(RpsvMatch $match): bool
    {
        if (! in_array($match->status, ['active', 'revealing', 'result'], true)) {
            return false;
        }

        if (! $match->phase_ends_at || $match->phase_ends_at->isFuture()) {
            return false;
        }

        $round = RpsvRound::query()
            ->where('rpsv_match_id', $match->id)
            ->where('round_number', $match->current_round)
            ->lockForUpdate()
            ->first();

        if ($match->phase === 'intro' || $match->phase === 'ready' || $match->phase === 'idle' || $match->phase === 'select') {
            // No timeouts in this flow — turn-by-turn until both have chosen.
            return false;
        }

        if (! $round) {
            return false;
        }

        if ($match->phase === 'reveal') {
            $round->update([
                'status' => 'revealed',
                'result_ends_at' => now()->addSeconds(self::RESULT_SECONDS),
            ]);
            $match->update([
                'status' => 'result',
                'phase' => 'result',
                'phase_ends_at' => now()->addSeconds(self::RESULT_SECONDS),
            ]);

            return true;
        }

        if ($match->phase === 'result') {
            $round->update(['status' => 'done']);
            $p1 = (int) $match->player1_score;
            $p2 = (int) $match->player2_score;
            $winsNeeded = self::WINS_NEEDED;

            // First to 2 wins — draws do not count; keep playing until someone reaches 2.
            if ($p1 >= $winsNeeded || $p2 >= $winsNeeded) {
                $this->completeMatch($match, $p1 > $p2 ? $match->player1_id : $match->player2_id);

                return true;
            }

            $this->startNextRound($match, $round->round_number + 1);

            return true;
        }

        return false;
    }

    private function beginSelectPhase(RpsvMatch $match, ?RpsvRound $round): void
    {
        if (! $round) {
            $round = RpsvRound::query()->create([
                'rpsv_match_id' => $match->id,
                'round_number' => max(1, (int) $match->current_round),
                'status' => 'open',
            ]);
        }

        $this->beginIdlePhase($match);
        $round->refresh();
    }

    private function startNextRound(RpsvMatch $match, int $roundNumber): void
    {
        RpsvRound::query()->create([
            'rpsv_match_id' => $match->id,
            'round_number' => $roundNumber,
            'status' => 'open',
        ]);

        $match->update([
            'current_round' => $roundNumber,
            'max_rounds' => self::MAX_ROUNDS,
            'status' => 'active',
            'phase' => 'idle',
            'phase_ends_at' => null,
        ]);
    }

    private function completeMatch(RpsvMatch $match, ?int $winnerId): void
    {
        $matchUrl = route('user.rpsv.match', $match);
        $forfeitBy = $match->forfeit_by;

        if (! $winnerId) {
            // Score tie after all rounds — refund full stake to both players.
            foreach ([$match->player1_id, $match->player2_id] as $uid) {
                if (! $uid) {
                    continue;
                }
                $player = User::query()->whereKey($uid)->lockForUpdate()->firstOrFail();
                $this->wallet->credit(
                    $player,
                    $match->currency,
                    (float) $match->stake,
                    TransactionType::RpsvStakeRefund,
                    'RPSV draw refund #'.$match->id,
                    $match
                );
                $this->notifications->notifyUser(
                    $player,
                    'rpsv',
                    'RPSV Draw',
                    'Match ended in a draw ('.$match->player1_score.'–'.$match->player2_score.'). '
                    .$this->money($match->currency, (float) $match->stake).' stake refunded to your wallet.',
                    ['url' => $matchUrl],
                );
            }
            $match->update([
                'status' => 'completed',
                'phase' => 'result',
                'winner_id' => null,
                'completed_at' => now(),
                'phase_ends_at' => null,
            ]);

            return;
        }

        $winner = User::query()->whereKey($winnerId)->lockForUpdate()->firstOrFail();
        $this->wallet->credit(
            $winner,
            $match->currency,
            (float) $match->winner_payout,
            TransactionType::RpsvWin,
            'RPSV win #'.$match->id,
            $match
        );

        $scoreLine = $match->player1_score.'–'.$match->player2_score;
        $winMsg = $forfeitBy
            ? 'Opponent forfeited. You won '.$this->money($match->currency, (float) $match->winner_payout).'.'
            : 'You won the match ('.$scoreLine.'). '.$this->money($match->currency, (float) $match->winner_payout).' credited (1.90× stake).';

        $this->notifications->notifyUser(
            $winner,
            'rpsv',
            'RPSV Win',
            $winMsg,
            ['url' => $matchUrl],
        );

        $loserId = $match->player1_id === $winnerId ? $match->player2_id : $match->player1_id;
        if ($loserId) {
            $loser = User::query()->whereKey($loserId)->first();
            if ($loser) {
                $lossMsg = $forfeitBy === $loserId
                    ? 'You forfeited the match. Stake '.$this->money($match->currency, (float) $match->stake).' was lost.'
                    : 'You lost the match ('.$scoreLine.'). Stake '.$this->money($match->currency, (float) $match->stake).' was taken.';

                $this->notifications->notifyUser(
                    $loser,
                    'rpsv',
                    $forfeitBy === $loserId ? 'RPSV Forfeit' : 'RPSV Loss',
                    $lossMsg,
                    ['url' => $matchUrl],
                );
            }
        }

        $match->update([
            'status' => 'completed',
            'phase' => 'result',
            'winner_id' => $winnerId,
            'completed_at' => now(),
            'phase_ends_at' => null,
        ]);
    }

    public function expireInvites(): void
    {
        // Lobby polls every ~1s; do not re-scan/lock invites that often (blocks accept).
        $lockKey = 'rpsv:expire_invites_lock';
        if (! cache()->add($lockKey, 1, now()->addSeconds(20))) {
            return;
        }

        $pending = RpsvInvite::query()
            ->where('status', 'pending')
            ->limit(100)
            ->get();

        $expired = $pending->filter(fn (RpsvInvite $invite) => $this->inviteHasExpired($invite));

        foreach ($expired as $invite) {
            DB::transaction(function () use ($invite) {
                $invite = RpsvInvite::query()->whereKey($invite->id)->lockForUpdate()->first();
                if (! $invite || $invite->status !== 'pending') {
                    return;
                }
                $invite->update(['status' => 'expired']);
                $match = RpsvMatch::query()->whereKey($invite->match_id)->lockForUpdate()->first();
                if ($match && in_array($match->status, ['waiting', 'queued'], true) && ! $match->player2_id) {
                    $sender = User::query()->whereKey($invite->sender_id)->lockForUpdate()->firstOrFail();
                    $this->wallet->credit(
                        $sender,
                        $match->currency,
                        (float) $match->stake,
                        TransactionType::RpsvStakeRefund,
                        'RPSV invite expired refund #'.$match->id,
                        $match
                    );
                    $this->notifyRefund(
                        $sender,
                        $match->currency,
                        (float) $match->stake,
                        $match,
                        'Invite expired — stake refunded to your wallet.'
                    );
                    $match->update(['status' => 'cancelled', 'completed_at' => now()]);
                }
            });
        }

        $orphans = RpsvMatch::query()
            ->where('mode', 'invite')
            ->whereIn('status', ['waiting', 'queued'])
            ->whereNull('player2_id')
            ->whereDoesntHave('invite', fn ($q) => $q->where('status', 'pending'))
            ->limit(50)
            ->get();

        foreach ($orphans as $match) {
            DB::transaction(function () use ($match) {
                $locked = RpsvMatch::query()->whereKey($match->id)->lockForUpdate()->first();
                if (! $locked || ! in_array($locked->status, ['waiting', 'queued'], true) || $locked->player2_id) {
                    return;
                }
                $sender = User::query()->whereKey($locked->player1_id)->lockForUpdate()->first();
                if ($sender) {
                    $this->wallet->credit(
                        $sender,
                        $locked->currency,
                        (float) $locked->stake,
                        TransactionType::RpsvStakeRefund,
                        'RPSV orphan invite refund #'.$locked->id,
                        $locked
                    );
                }
                $locked->update(['status' => 'cancelled', 'completed_at' => now()]);
            });
        }
    }

    /**
     * @return array{incoming: \Illuminate\Support\Collection<int, RpsvInvite>, outgoing: \Illuminate\Support\Collection<int, RpsvInvite>}
     */
    public function pendingInvitesFor(User $user): array
    {
        return [
            'incoming' => RpsvInvite::query()
                ->with('sender:id,name,member_id')
                ->where('receiver_id', $user->id)
                ->where('status', 'pending')
                ->latest('id')
                ->get(),
            'outgoing' => RpsvInvite::query()
                ->with('receiver:id,name,member_id')
                ->where('sender_id', $user->id)
                ->where('status', 'pending')
                ->latest('id')
                ->get(),
        ];
    }

    public function liveMatchFor(User $user, bool $sync = true): ?RpsvMatch
    {
        $match = RpsvMatch::query()
            ->where(function ($q) use ($user) {
                $q->where('player1_id', $user->id)->orWhere('player2_id', $user->id);
            })
            ->whereIn('status', ['active', 'revealing', 'result'])
            ->latest('id')
            ->first();

        if (! $match) {
            return null;
        }

        if ($sync) {
            $match = $this->syncMatch($match);
        }

        return in_array($match->status, ['active', 'revealing', 'result'], true) ? $match : null;
    }

    public function queuedQuickMatchFor(User $user): ?RpsvMatch
    {
        return RpsvMatch::query()
            ->where('player1_id', $user->id)
            ->where('mode', 'quick')
            ->whereIn('status', ['waiting', 'queued'])
            ->latest('id')
            ->first();
    }

    private function createShellMatch(User $player1, string $mode, string $currency, float $stake): RpsvMatch
    {
        $pool = $stake * 2;
        $fee = round($pool * self::FEE_RATE, 8);
        $payout = round($stake * self::MULTIPLIER, 8);

        return RpsvMatch::query()->create([
            'match_code' => strtoupper(Str::random(8)),
            'mode' => $mode,
            'currency' => $currency,
            'stake' => $stake,
            'prize_pool' => $pool,
            'platform_fee' => $fee,
            'winner_payout' => $payout,
            'player1_id' => $player1->id,
            'max_rounds' => self::MAX_ROUNDS,
            'status' => 'waiting',
            'phase' => 'select',
        ]);
    }

    private function assertNotInActiveMatch(int $userId): void
    {
        $outgoingPending = RpsvInvite::query()
            ->where('sender_id', $userId)
            ->where('status', 'pending')
            ->exists();

        $busy = RpsvMatch::query()
            ->where(function ($q) use ($userId) {
                $q->where('player1_id', $userId)->orWhere('player2_id', $userId);
            })
            ->whereIn('status', ['queued', 'active', 'revealing', 'result'])
            ->exists();

        $waitingInvite = RpsvMatch::query()
            ->where('player1_id', $userId)
            ->where('mode', 'invite')
            ->whereIn('status', ['waiting', 'queued'])
            ->exists();

        if ($outgoingPending || $waitingInvite) {
            throw ValidationException::withMessages([
                'username' => 'You already have a pending RPSV invitation. Cancel it from My Invitations first.',
            ]);
        }

        if ($busy) {
            throw ValidationException::withMessages([
                'match' => 'You already have an active RPSV match.',
            ]);
        }
    }

    private function inviteHasExpired(RpsvInvite $invite): bool
    {
        if ($invite->expires_at) {
            return $invite->expires_at->isPast();
        }

        $born = $invite->created_at ?? now();

        return $born->copy()->addMinutes(self::INVITE_TTL_MINUTES)->isPast();
    }

    private function findPlayer(string $username): ?User
    {
        $username = trim(preg_replace('/\s+/', ' ', $username) ?? '');
        if ($username === '') {
            return null;
        }

        $needle = mb_strtolower($username);

        return User::query()
            ->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(name) = ?', [$needle])
                    ->orWhereRaw('LOWER(email) = ?', [$needle])
                    ->orWhereRaw('LOWER(member_id) = ?', [$needle])
                    ->orWhereRaw('LOWER(referral_code) = ?', [$needle]);
            })
            ->first();
    }

    private function normalizeCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        if ($currency === 'USD') {
            return 'USD';
        }

        $resolved = \App\Enums\WalletCurrency::tryNormalize($currency);
        if (! $resolved) {
            throw ValidationException::withMessages(['currency' => 'Select a supported gaming currency.']);
        }

        // Keep legacy USD label for USDT so existing match UI stays consistent.
        return $resolved === \App\Enums\WalletCurrency::Usdt ? 'USD' : $resolved->value;
    }

    /**
     * Lobby stakes for crypto are always USD amounts. Convert to native wallet units
     * at lock-time so BTC/ETH/etc players debit the correct equivalent.
     *
     * @return array{native: float, queue_key: float}
     */
    private function resolveStakeForPlay(string $currency, float $stakeInput): array
    {
        $currency = strtoupper(trim($currency));

        if ($currency === 'NGN') {
            $native = $this->normalizeStakeAgainstPresets($stakeInput, self::STAKE_PRESETS_NGN, 2);

            return ['native' => $native, 'queue_key' => $native];
        }

        $usd = $this->normalizeStakeAgainstPresets($stakeInput, self::STAKE_PRESETS_USD, 2);
        $walletCurrency = $this->wallet->toWalletCurrency($currency);
        $decimals = in_array($walletCurrency, [\App\Enums\WalletCurrency::Usdt, \App\Enums\WalletCurrency::Usdc], true) ? 2 : 8;
        $native = round($this->rates->cryptoAmountForUsd($walletCurrency, $usd), $decimals);

        if ($native <= 0) {
            throw ValidationException::withMessages([
                'stake' => 'Unable to price this stake in '.$currency.'. Please try again.',
            ]);
        }

        return ['native' => $native, 'queue_key' => $usd];
    }

    /**
     * @param  list<float>  $presets
     */
    private function normalizeStakeAgainstPresets(float $stake, array $presets, int $decimals): float
    {
        $stake = round($stake, $decimals);
        $allowed = array_map(fn ($v) => round((float) $v, $decimals), $presets);

        foreach ($allowed as $preset) {
            if (abs($stake - $preset) <= max(1e-8, $preset * 0.0001)) {
                return $preset;
            }
        }

        throw ValidationException::withMessages(['stake' => 'Select a valid stake amount.']);
    }

    private function normalizeStake(float $stake, string $currency = 'USD'): float
    {
        return $this->resolveStakeForPlay($currency, $stake)['native'];
    }

    private function randomMove(): string
    {
        return ['rock', 'paper', 'scissors'][random_int(0, 2)];
    }

    private function roundWinnerId(RpsvMatch $match, ?string $m1, ?string $m2): ?int
    {
        if ($m1 && ! $m2) {
            return $match->player1_id;
        }
        if ($m2 && ! $m1) {
            return $match->player2_id;
        }
        if (! $m1 || ! $m2 || $m1 === $m2) {
            return null;
        }

        $wins = [
            'rock' => 'scissors',
            'paper' => 'rock',
            'scissors' => 'paper',
        ];

        if (($wins[$m1] ?? null) === $m2) {
            return $match->player1_id;
        }

        return $match->player2_id;
    }

    /**
     * @return array{kind: string, title: string, subtitle: string}|null
     */
    private function bannerFor(RpsvMatch $match, int $userId, ?RpsvRound $current): ?array
    {
        if (in_array($match->phase, ['ready'], true)) {
            return null;
        }

        if (! $current) {
            return null;
        }

        $isP1 = $match->player1_id === $userId;
        $youMove = $isP1 ? $current->player1_move : $current->player2_move;
        $oppMove = $isP1 ? $current->player2_move : $current->player1_move;
        $opp = $isP1 ? $match->player2 : $match->player1;
        $oppName = $opp?->name ?: 'Opponent';
        $roundNo = (int) $current->round_number;
        $winsNeeded = self::WINS_NEEDED;
        $p1Name = $match->player1?->name ?: 'Player 1';
        $p2Name = $match->player2?->name ?: 'Player 2';

        if ($match->status === 'active' && in_array($match->phase, ['idle', 'select'], true)) {
            if (! $current->player1_move) {
                return [
                    'kind' => 'turn',
                    'title' => $isP1 ? 'Player 1, make your choice.' : 'Waiting for Player 1',
                    'subtitle' => $isP1
                        ? 'Select Rock, Paper, or Scissors. '.$p2Name.' chooses after you.'
                        : $p1Name.' chooses first. Your turn comes next.',
                ];
            }

            if ($current->player1_move && ! $current->player2_move) {
                return [
                    'kind' => 'turn',
                    'title' => $isP1 ? 'Waiting for Player 2' : 'Player 2, make your choice.',
                    'subtitle' => $isP1
                        ? $p2Name.' is selecting now.'
                        : 'Select Rock, Paper, or Scissors to finish this round.',
                ];
            }
        }

        if (! $youMove && ! $oppMove) {
            return null;
        }

        $outcome = $this->roundOutcome($match, $userId, $current);
        $youLabel = $youMove ? ucfirst($youMove) : 'No move';
        $oppLabel = $oppMove ? ucfirst($oppMove) : 'No move';
        $vsLine = 'You: '.$youLabel.'  ·  '.$oppName.': '.$oppLabel;

        if ($match->phase === 'reveal') {
            return [
                'kind' => 'reveal',
                'title' => $vsLine,
                'subtitle' => 'Round '.$roundNo.' — first to '.$winsNeeded.' wins',
            ];
        }

        if ($match->phase === 'result' || in_array($match->status, ['completed', 'forfeit'], true)) {
            if (in_array($match->status, ['completed', 'forfeit'], true)) {
                $matchResult = $this->matchResultFor($match, $userId);
                if ($matchResult) {
                    return [
                        'kind' => $matchResult['kind'],
                        'title' => $matchResult['title'],
                        'subtitle' => $matchResult['message'],
                    ];
                }
            }

            $title = match ($outcome) {
                'win' => 'You win this round',
                'loss' => 'You lose this round',
                default => 'This round is a draw',
            };

            $youScore = (int) ($isP1 ? $match->player1_score : $match->player2_score);
            $oppScore = (int) ($isP1 ? $match->player2_score : $match->player1_score);
            $matchOver = $youScore >= $winsNeeded || $oppScore >= $winsNeeded
                || in_array($match->status, ['completed', 'forfeit'], true);
            $scoreLine = $youScore.' : '.$oppScore;

            return [
                'kind' => $outcome ?? 'draw',
                'title' => $title,
                'subtitle' => $vsLine.' · Score '.$scoreLine.($matchOver ? ' — match complete' : ' — first to '.$winsNeeded),
            ];
        }

        return null;
    }

    /**
     * Final match payout summary for UI / toast / end screen.
     *
     * @return array{kind: string, title: string, message: string, amount: float}|null
     */
    private function matchResultFor(RpsvMatch $match, int $userId): ?array
    {
        if (! in_array($match->status, ['completed', 'forfeit'], true)) {
            return null;
        }

        $score = ((int) $match->player1_score).'–'.((int) $match->player2_score);
        $stake = (float) $match->stake;
        $payout = (float) $match->winner_payout;

        if ($match->winner_id === null) {
            return [
                'kind' => 'draw',
                'title' => 'Match draw',
                'message' => 'Final score '.$score.'. Stake '.$this->money($match->currency, $stake).' refunded to both players.',
                'amount' => $stake,
            ];
        }

        if ((int) $match->winner_id === $userId) {
            return [
                'kind' => 'win',
                'title' => 'You won the match',
                'message' => 'Final score '.$score.'. '.$this->money($match->currency, $payout).' credited to your wallet.',
                'amount' => $payout,
            ];
        }

        return [
            'kind' => 'loss',
            'title' => 'You lost the match',
            'message' => 'Final score '.$score.'. Stake '.$this->money($match->currency, $stake).' was lost.',
            'amount' => $stake,
        ];
    }

    /**
     * @return array{kind: string, title: string, message?: string, round?: int}|null
     */
    private function toastFor(RpsvMatch $match, int $userId, ?RpsvRound $current): ?array
    {
        if (in_array($match->status, ['completed', 'forfeit'], true)) {
            $matchResult = $this->matchResultFor($match, $userId);
            if ($matchResult) {
                return [
                    'kind' => $matchResult['kind'],
                    'title' => $matchResult['title'],
                    'message' => $matchResult['message'],
                    'round' => 'match:'.(int) $match->id,
                ];
            }
        }

        if ($match->phase !== 'result') {
            return null;
        }

        $outcome = $this->roundOutcome($match, $userId, $current);
        if (! $outcome) {
            return null;
        }

        $title = match ($outcome) {
            'win' => 'You win this round',
            'loss' => 'You lose this round',
            default => 'Round draw',
        };

        $isP1 = $match->player1_id === $userId;
        $youMove = $current ? ($isP1 ? $current->player1_move : $current->player2_move) : null;
        $oppMove = $current ? ($isP1 ? $current->player2_move : $current->player1_move) : null;

        $message = trim(
            ($youMove ? 'You: '.ucfirst($youMove) : 'You: —')
            .'  vs  '
            .($oppMove ? ucfirst($oppMove) : '—')
        );

        return [
            'kind' => $outcome,
            'title' => $title,
            'message' => $message,
            'round' => (int) ($current?->round_number ?? $match->current_round),
        ];
    }

    private function roundOutcome(RpsvMatch $match, int $userId, ?RpsvRound $current): ?string
    {
        if (! $current || (! $current->player1_move && ! $current->player2_move)) {
            return null;
        }

        if (! in_array($match->phase, ['reveal', 'result'], true) && ! in_array($match->status, ['completed', 'forfeit'], true)) {
            return null;
        }

        if ($current->winner_id === null) {
            return 'draw';
        }

        return $current->winner_id === $userId ? 'win' : 'loss';
    }

    private function money(string $currency, float $amount): string
    {
        $code = strtoupper($currency);

        if ($code === 'NGN') {
            return '₦'.number_format($amount, 2);
        }

        if (in_array($code, ['USD', 'USDT', 'USDC'], true)) {
            return '$'.number_format($amount, 2);
        }

        $decimals = $amount >= 1 ? 4 : 8;

        return rtrim(rtrim(number_format($amount, $decimals, '.', ''), '0'), '.').' '.$code;
    }

    private function notifyStake(User $user, string $currency, float $amount, RpsvMatch $match, string $detail): void
    {
        $this->notifications->notifyUser(
            $user,
            'rpsv',
            'RPSV Stake Deducted',
            $this->money($currency, $amount).' deducted from your wallet. '.$detail,
            ['url' => route('user.rpsv.index')],
        );
    }

    private function notifyRefund(User $user, string $currency, float $amount, RpsvMatch $match, string $detail): void
    {
        $this->notifications->notifyUser(
            $user,
            'rpsv',
            'RPSV Stake Refunded',
            $this->money($currency, $amount).' credited back to your wallet. '.$detail,
            ['url' => route('user.rpsv.index')],
        );
    }
}
