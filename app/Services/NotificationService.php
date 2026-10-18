<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\WithdrawalStatus;
use App\Enums\WithdrawalType;
use App\Models\AdminNotificationRead;
use App\Models\Deposit;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * @return array{count: int, items: Collection<int, array{id: string, title: string, message: string, time: Carbon, type: string, unread: bool, url: string}>}
     */
    public function forAdmin(?User $admin = null): array
    {
        $admin = $admin ?? Auth::user();
        $readKeys = $this->adminReadKeys($admin);
        $items = collect();

        Deposit::query()
            ->with('user')
            ->where('status', DepositStatus::Pending)
            ->latest()
            ->limit(8)
            ->get()
            ->each(function (Deposit $deposit) use ($items, $readKeys) {
                $key = 'deposit-'.$deposit->id;

                $items->push([
                    'id' => $key,
                    'item_type' => 'deposit',
                    'item_id' => $deposit->id,
                    'title' => 'Pending Deposit',
                    'message' => ($deposit->user?->name ?? 'User').' — '.number_format((float) $deposit->amount, 2).' USDT',
                    'time' => $deposit->created_at ?? now(),
                    'type' => 'deposit',
                    'unread' => ! isset($readKeys[$key]),
                    'url' => route('admin.notifications.read', ['type' => 'deposit', 'id' => $deposit->id]),
                ]);
            });

        WithdrawalRequest::query()
            ->with('user')
            ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved])
            ->latest()
            ->limit(8)
            ->get()
            ->each(function (WithdrawalRequest $withdrawal) use ($items, $readKeys) {
                $key = 'withdrawal-'.$withdrawal->id;
                $typeLabel = $withdrawal->type === WithdrawalType::Capital ? 'Capital' : 'Reward';

                $items->push([
                    'id' => $key,
                    'item_type' => 'withdrawal',
                    'item_id' => $withdrawal->id,
                    'title' => "Pending {$typeLabel} Withdrawal",
                    'message' => ($withdrawal->user?->name ?? 'User').' — '.number_format((float) $withdrawal->amount, 2).' USDT',
                    'time' => $withdrawal->created_at ?? now(),
                    'type' => 'withdrawal',
                    'unread' => ! isset($readKeys[$key]),
                    'url' => route('admin.notifications.read', ['type' => 'withdrawal', 'id' => $withdrawal->id]),
                ]);
            });

        SupportTicket::query()
            ->where('status', SupportTicket::STATUS_OPEN)
            ->latest()
            ->limit(8)
            ->get()
            ->each(function (SupportTicket $ticket) use ($items, $readKeys) {
                $key = 'support-'.$ticket->id;

                $items->push([
                    'id' => $key,
                    'item_type' => 'support',
                    'item_id' => $ticket->id,
                    'title' => $ticket->user_id === null ? 'New Contact Message' : 'New Support Ticket',
                    'message' => $ticket->name.' — '.$ticket->subject_label.': '.Str::limit($ticket->message, 60),
                    'time' => $ticket->created_at ?? now(),
                    'type' => 'support',
                    'unread' => ! isset($readKeys[$key]),
                    'url' => route('admin.notifications.read', ['type' => 'support', 'id' => $ticket->id]),
                ]);
            });

        $sorted = $items->sortByDesc('time')->values()->take(10);

        return [
            'count' => $sorted->where('unread', true)->count(),
            'items' => $sorted,
        ];
    }

    /**
     * @return array{count: int, items: Collection<int, array{id: int, title: string, message: string, time: Carbon, type: string, unread: bool, url: string}>}
     */
    public function forUser(User $user): array
    {
        $records = UserNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(10)
            ->get();

        $items = $records->map(fn (UserNotification $notification) => [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'time' => $notification->created_at ?? now(),
            'type' => $notification->type,
            'unread' => $notification->read_at === null,
            'url' => route('user.notifications.read', $notification),
        ]);

        return [
            'count' => UserNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
            'items' => $items,
        ];
    }

    public function notifyUser(User $user, string $type, string $title, string $message, array $data = []): UserNotification
    {
        return UserNotification::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data ?: null,
        ]);
    }

    public function markUserAsRead(UserNotification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllUserAsRead(User $user, ?array $types = null): int
    {
        $query = UserNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at');

        if ($types !== null) {
            $query->whereIn('type', $types);
        }

        return $query->update(['read_at' => now()]);
    }

    /**
     * @return array<string, array{label: string, icon: string}>
     */
    public function userNotificationFilters(): array
    {
        return [
            'all' => ['label' => 'All', 'icon' => 'fa-solid fa-grip'],
            'account' => ['label' => 'Account', 'icon' => 'fa-regular fa-user'],
            'transactions' => ['label' => 'Transactions', 'icon' => 'fa-solid fa-right-left'],
            'investments' => ['label' => 'Investments', 'icon' => 'fa-solid fa-chart-line'],
            'more' => ['label' => 'More', 'icon' => 'fa-solid fa-ellipsis'],
        ];
    }

    /**
     * @return list<string>|null
     */
    public function typesForFilter(string $filter): ?array
    {
        return match ($filter) {
            'account' => ['welcome', 'account', 'kyc'],
            'transactions' => ['deposit', 'withdrawal', 'wallet', 'reward', 'referral'],
            'investments' => ['investment', 'savings', 'lending'],
            'more' => ['announcement', 'prediction', 'azubogu', 'rpsv'],
            default => null,
        };
    }

    /**
     * @return array{icon: string, tone: string}
     */
    public function presentationForType(string $type): array
    {
        return match ($type) {
            'welcome' => ['icon' => 'fa-solid fa-shield-halved', 'tone' => 'green'],
            'account', 'kyc' => ['icon' => 'fa-solid fa-user-shield', 'tone' => 'blue'],
            'deposit', 'wallet' => ['icon' => 'fa-solid fa-wallet', 'tone' => 'mint'],
            'withdrawal' => ['icon' => 'fa-solid fa-money-bill-transfer', 'tone' => 'orange'],
            'reward', 'referral' => ['icon' => 'fa-solid fa-gift', 'tone' => 'purple'],
            'investment', 'savings', 'lending' => ['icon' => 'fa-solid fa-chart-line', 'tone' => 'orange'],
            'prediction', 'azubogu', 'rpsv' => ['icon' => 'fa-solid fa-gamepad', 'tone' => 'blue'],
            'announcement' => ['icon' => 'fa-solid fa-bullhorn', 'tone' => 'slate'],
            default => ['icon' => 'fa-solid fa-gear', 'tone' => 'slate'],
        };
    }

    public function formatUserNotificationTime(?Carbon $time): string
    {
        $time ??= now();

        if ($time->isToday()) {
            $minutes = max(1, (int) $time->diffInMinutes(now(), absolute: true));

            if ($minutes < 60) {
                return $minutes.' min ago';
            }

            $hours = max(1, (int) $time->diffInHours(now(), absolute: true));

            return $hours.' hour'.($hours === 1 ? '' : 's').' ago';
        }

        if ($time->isYesterday()) {
            return 'Yesterday '.$time->format('g:i A');
        }

        return $time->format('M d, Y g:i A');
    }

    public function markAdminItemAsRead(User $admin, string $itemType, int $itemId): void
    {
        AdminNotificationRead::query()->firstOrCreate([
            'admin_id' => $admin->id,
            'item_type' => $itemType,
            'item_id' => $itemId,
        ]);
    }

    public function markAllAdminAsRead(User $admin): int
    {
        $now = now();
        $rows = [];

        $push = function (string $type, int $id) use (&$rows, $admin, $now): void {
            $rows[] = [
                'admin_id' => $admin->id,
                'item_type' => $type,
                'item_id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        };

        Deposit::query()
            ->where('status', DepositStatus::Pending)
            ->pluck('id')
            ->each(fn ($id) => $push('deposit', (int) $id));

        WithdrawalRequest::query()
            ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Approved])
            ->pluck('id')
            ->each(fn ($id) => $push('withdrawal', (int) $id));

        SupportTicket::query()
            ->where('status', SupportTicket::STATUS_OPEN)
            ->pluck('id')
            ->each(fn ($id) => $push('support', (int) $id));

        if ($rows === []) {
            return 0;
        }

        AdminNotificationRead::query()->upsert(
            $rows,
            ['admin_id', 'item_type', 'item_id'],
            ['updated_at'],
        );

        return count($rows);
    }

    public function urlForUserNotification(UserNotification $notification): string
    {
        $dataUrl = is_array($notification->data) ? ($notification->data['url'] ?? null) : null;

        if ($dataUrl) {
            return $dataUrl;
        }

        return match ($notification->type) {
            'deposit' => route('user.deposit.index'),
            'withdrawal' => route('user.withdraw.index'),
            'reward' => route('user.earnings.index'),
            'referral' => route('user.referrals.index'),
            'prediction' => route('user.prediction.index'),
            'azubogu' => route('user.azubogu.index'),
            'rpsv' => route('user.rpsv.index'),
            'wallet' => route('user.wallets.index'),
            'announcement' => route('user.dashboard'),
            'account' => route('user.support.index'),
            default => route('user.dashboard'),
        };
    }

    public function urlForAdminItem(string $type, int $id): string
    {
        return match ($type) {
            'deposit' => route('admin.deposits.index', ['status' => 'pending']),
            'withdrawal' => $this->withdrawalAdminUrl($id),
            'support' => route('admin.support.show', $id),
            default => route('admin.dashboard'),
        };
    }

    /**
     * @return array<string, true>
     */
    private function adminReadKeys(?User $admin): array
    {
        if (! $admin) {
            return [];
        }

        return AdminNotificationRead::query()
            ->where('admin_id', $admin->id)
            ->get()
            ->mapWithKeys(fn (AdminNotificationRead $read) => [
                $read->item_type.'-'.$read->item_id => true,
            ])
            ->all();
    }

    private function withdrawalAdminUrl(int $id): string
    {
        $withdrawal = WithdrawalRequest::query()->find($id);
        $type = $withdrawal?->type === WithdrawalType::Capital ? 'capital' : 'reward';

        return route('admin.withdrawals.index', [
            'status' => 'pending',
            'type' => $type,
        ]);
    }
}
