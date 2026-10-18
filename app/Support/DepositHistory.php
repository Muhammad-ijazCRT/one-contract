<?php

namespace App\Support;

use App\Enums\WalletCurrency;
use App\Models\Deposit;
use App\Models\FiatRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class DepositHistory
{
    public static function paginate(User $user, bool $pendingOnly = false, int $perPage = 10): LengthAwarePaginator
    {
        $rows = self::rows($user, $pendingOnly);
        $page = Paginator::resolveCurrentPage();

        return new Paginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public static function pendingCount(User $user): int
    {
        return $user->pendingDeposits()->count()
            + FiatRequest::query()
                ->where('user_id', $user->id)
                ->where('direction', 'on_ramp')
                ->where('status', 'pending')
                ->count();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function rows(User $user, bool $pendingOnly = false): Collection
    {
        $crypto = $user->deposits()
            ->when($pendingOnly, fn ($query) => $query->where('status', 'pending'))
            ->latest()
            ->get()
            ->map(fn (Deposit $deposit) => self::fromDeposit($deposit));

        $fiat = FiatRequest::query()
            ->where('user_id', $user->id)
            ->where('direction', 'on_ramp')
            ->when($pendingOnly, fn ($query) => $query->where('status', 'pending'))
            ->latest()
            ->get()
            ->map(fn (FiatRequest $request) => self::fromFiat($request));

        return $crypto
            ->concat($fiat)
            ->sortByDesc('sort_at')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromDeposit(Deposit $deposit): array
    {
        $wallet = $deposit->creditWallet();
        $status = $deposit->status->value;

        return [
            'key' => 'd-'.$deposit->id,
            'id_label' => '#'.$deposit->id,
            'amount_label' => self::amountLabel($wallet, (float) $deposit->amount),
            'wallet' => $wallet,
            'status' => $status,
            'network' => $deposit->network ? strtoupper((string) $deposit->network) : '—',
            'date' => $deposit->deposited_at ?? $deposit->created_at,
            'sort_at' => ($deposit->deposited_at ?? $deposit->created_at)?->timestamp ?? 0,
            'url' => route('user.deposit.show', $deposit),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromFiat(FiatRequest $request): array
    {
        $wallet = $request->creditWallet();
        $url = ($request->status === 'pending' && filled($request->checkout_url))
            ? (string) $request->checkout_url
            : route('user.deposit.index');

        return [
            'key' => 'f-'.$request->id,
            'id_label' => '#N'.$request->id,
            'amount_label' => self::amountLabel($wallet, (float) $request->amount),
            'wallet' => $wallet,
            'status' => $request->status === 'approved' ? 'completed' : $request->status,
            'network' => $request->gateway === 'monnify' ? 'Monnify' : 'Bank',
            'date' => $request->processed_at ?? $request->created_at,
            'sort_at' => ($request->processed_at ?? $request->created_at)?->timestamp ?? 0,
            'url' => $url,
        ];
    }

    public static function amountLabel(WalletCurrency $wallet, float $amount): string
    {
        return match ($wallet) {
            WalletCurrency::Ngn => '₦'.number_format($amount, 2),
            WalletCurrency::Usdt, WalletCurrency::Usdc => number_format($amount, 2).' '.$wallet->value,
            default => number_format($amount, 2).' USD',
        };
    }
}
