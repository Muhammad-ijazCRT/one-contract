<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Enums\WalletCurrency;
use App\Models\RpsvInvite;
use App\Models\RpsvMatch;
use App\Services\RpsvGameService;
use App\Services\RpsvWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RpsvGameController extends Controller
{
    public function __construct(
        private readonly RpsvGameService $game,
        private readonly RpsvWalletService $wallet,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $this->game->expireInvites();
        $invites = $this->game->pendingInvitesFor($user);
        $active = $this->game->liveMatchFor($user);

        if ($active) {
            return redirect()->route('user.rpsv.match', $active);
        }

        $preferred = $user->preferredWalletCurrency();
        $category = $preferred === WalletCurrency::Ngn ? 'fiat' : 'crypto';
        $activeCurrency = $this->wallet->activeMatchCurrency($user);
        if ($category === 'fiat') {
            $activeCurrency = 'NGN';
        } elseif (in_array(strtoupper($activeCurrency), ['NGN'], true)) {
            $activeCurrency = 'USD';
        }

        $cryptoWallets = collect(WalletCurrency::crypto())->map(function (WalletCurrency $currency) use ($user) {
            $balance = $this->wallet->balance($user, $currency->value);
            $decimals = $currency->decimals();

            return [
                'code' => $currency->value,
                'label' => $currency->label(),
                'icon' => $currency->iconUrl(),
                'decimals' => $decimals,
                'balance' => $balance,
                'formatted' => $currency === WalletCurrency::Usdt || $currency === WalletCurrency::Usdc
                    ? '$'.number_format($balance, 2)
                    : rtrim(rtrim(number_format($balance, $decimals, '.', ''), '0'), '.').' '.$currency->value,
                'match_code' => $currency === WalletCurrency::Usdt ? 'USD' : $currency->value,
            ];
        })->values()->all();

        $ngnBalance = $this->wallet->balance($user, 'NGN');

        return view('user.rpsv.index', [
            'stakes' => $this->game->stakePresetsFor($category === 'fiat' ? 'NGN' : 'USD'),
            'stakesByCurrency' => $this->game->allStakePresets(),
            'activeCurrency' => $activeCurrency,
            'activeCategory' => $category,
            'activeBalance' => $this->wallet->balance($user, $activeCurrency),
            'usdBalance' => $this->wallet->balance($user, 'USD'),
            'ngnBalance' => $ngnBalance,
            'ngnFormatted' => '₦'.number_format($ngnBalance, 2),
            'cryptoWallets' => $cryptoWallets,
            'queued' => $this->game->queuedQuickMatchFor($user),
            'incoming' => $invites['incoming'],
            'outgoing' => $invites['outgoing'],
            'inviteTab' => match ($request->query('tab')) {
                'mine' => 'mine',
                'send' => 'send',
                default => ($invites['incoming']->isNotEmpty() || $invites['outgoing']->isNotEmpty()) ? 'mine' : 'send',
            },
        ]);
    }

    public function queue(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'currency' => ['required', 'string', 'in:USD,'.WalletCurrency::allCodes()],
            'stake' => ['required', 'numeric'],
        ]);

        $currency = $data['currency'] ?? $this->wallet->activeMatchCurrency($request->user());
        $match = $this->game->joinQuickMatch($request->user(), $currency, (float) $data['stake']);

        return redirect()
            ->route('user.rpsv.index')
            ->with('success', 'Searching for a '.$currency.' opponent at '.$data['stake'].'…');
    }

    public function cancelQueue(Request $request): RedirectResponse
    {
        $this->game->cancelQueue($request->user());

        return back()->with('success', 'Queue cancelled. Stake refunded.');
    }

    public function invite(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'currency' => ['required', 'string', 'in:USD,'.WalletCurrency::allCodes()],
            'stake' => ['required', 'numeric'],
        ]);

        $invite = $this->game->sendInvite($request->user(), $data['username'], $data['currency'], (float) $data['stake']);

        return redirect()
            ->route('user.rpsv.index', [
                'tab' => 'mine',
                'invite_sent' => 1,
                'to' => $invite->receiver?->name ?: 'friend',
                'stake' => $data['currency'].' '.$data['stake'],
            ])
            ->with('success', 'Invitation sent. Your friend will see it under My Invitations.')
            ->with('rpsv_invite_popup', [
                'kind' => 'sent',
                'title' => 'Invitation sent',
                'message' => 'You invited '.($invite->receiver?->name ?: 'your friend').' for '.$data['currency'].' '.$data['stake'].'.',
            ]);
    }

    public function acceptInvite(Request $request, RpsvInvite $invite): RedirectResponse|JsonResponse
    {
        $match = $this->game->acceptInvite($request->user(), $invite);
        $redirect = route('user.rpsv.match', $match);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'status' => 'matched',
                'redirect' => $redirect,
            ]);
        }

        return redirect()->to($redirect)->with('success', 'Invite accepted. Press Start when both players are ready.');
    }

    public function declineInvite(Request $request, RpsvInvite $invite): RedirectResponse
    {
        $this->game->declineInvite($request->user(), $invite);

        return redirect()->route('user.rpsv.index', ['tab' => 'mine'])->with('success', 'Invite declined.');
    }

    public function cancelInvite(Request $request, RpsvInvite $invite): RedirectResponse
    {
        $this->game->cancelInvite($request->user(), $invite);

        return redirect()->route('user.rpsv.index', ['tab' => 'mine'])->with('success', 'Invite cancelled. Stake refunded.');
    }

    public function match(Request $request, RpsvMatch $match): View
    {
        abort_unless($match->isPlayer($request->user()->id), 403);

        return view('user.rpsv.match', [
            'match' => $match,
            'state' => $this->game->stateFor($request->user(), $match),
        ]);
    }

    public function state(Request $request, RpsvMatch $match): JsonResponse
    {
        return response()->json($this->game->stateFor($request->user(), $match));
    }

    public function ready(Request $request, RpsvMatch $match): JsonResponse
    {
        $updated = $this->game->markReady($request->user(), $match);

        return response()->json($this->game->stateFor($request->user(), $updated));
    }

    public function move(Request $request, RpsvMatch $match): JsonResponse
    {
        $data = $request->validate([
            'move' => ['required', 'in:rock,paper,scissors'],
        ]);

        $updated = $this->game->submitMove($request->user(), $match, $data['move']);

        return response()->json($this->game->stateFor($request->user(), $updated));
    }

    public function forfeit(Request $request, RpsvMatch $match): RedirectResponse
    {
        $this->game->forfeit($request->user(), $match);

        return redirect()->route('user.rpsv.index')->with('success', 'Match forfeited.');
    }

    public function queueStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->game->expireInvites();
        $invites = $this->game->pendingInvitesFor($user);
        // Lobby only needs to know a live match exists — skip phase sync for speed.
        $active = $this->game->liveMatchFor($user, false);

        $payload = [
            'incoming' => $this->serializeIncoming($invites['incoming']),
            'outgoing' => $this->serializeOutgoing($invites['outgoing']),
            'usd_balance' => $this->wallet->balance($user, 'USD'),
            'ngn_balance' => $this->wallet->balance($user, 'NGN'),
            'active_currency' => $this->wallet->activeMatchCurrency($user),
            'active_balance' => $this->wallet->balance($user, $this->wallet->activeMatchCurrency($user)),
            'crypto_wallets' => collect(WalletCurrency::crypto())->map(function (WalletCurrency $currency) use ($user) {
                $balance = $this->wallet->balance($user, $currency->value);

                return [
                    'code' => $currency->value,
                    'match_code' => $currency === WalletCurrency::Usdt ? 'USD' : $currency->value,
                    'balance' => $balance,
                    'formatted' => $currency === WalletCurrency::Usdt || $currency === WalletCurrency::Usdc
                        ? '$'.number_format($balance, 2)
                        : rtrim(rtrim(number_format($balance, $currency->decimals(), '.', ''), '0'), '.').' '.$currency->value,
                ];
            })->values()->all(),
            'unread_notifications' => $user->userNotifications()->whereNull('read_at')->count(),
        ];

        if ($active) {
            return response()->json([
                'status' => 'matched',
                'redirect' => route('user.rpsv.match', $active),
                ...$payload,
            ]);
        }

        $queued = (bool) $this->game->queuedQuickMatchFor($user);

        return response()->json([
            'status' => $queued ? 'queued' : 'idle',
            ...$payload,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, RpsvInvite>  $invites
     * @return list<array<string, mixed>>
     */
    private function serializeIncoming($invites): array
    {
        return $invites->map(function (RpsvInvite $inv) {
            return [
                'id' => $inv->id,
                'from' => $inv->sender?->name ?: 'Player',
                'member_id' => $inv->sender?->member_id,
                'label' => $this->formatInviteStakeLabel($inv),
                'accept_url' => route('user.rpsv.invite.accept', $inv),
                'decline_url' => route('user.rpsv.invite.decline', $inv),
            ];
        })->values()->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, RpsvInvite>  $invites
     * @return list<array<string, mixed>>
     */
    private function serializeOutgoing($invites): array
    {
        return $invites->map(function (RpsvInvite $inv) {
            return [
                'id' => $inv->id,
                'to' => $inv->receiver?->name ?: 'Player',
                'member_id' => $inv->receiver?->member_id,
                'label' => $this->formatInviteStakeLabel($inv),
                'cancel_url' => route('user.rpsv.invite.cancel', $inv),
            ];
        })->values()->all();
    }

    private function formatInviteStakeLabel(RpsvInvite $inv): string
    {
        $code = strtoupper((string) $inv->currency);
        $amount = (float) $inv->stake;

        if ($code === 'NGN') {
            return $code.' ₦'.number_format($amount, 2);
        }

        if (in_array($code, ['USD', 'USDT', 'USDC'], true)) {
            return $code.' $'.number_format($amount, 2);
        }

        return $code.' '.rtrim(rtrim(number_format($amount, 8, '.', ''), '0'), '.');
    }
}
