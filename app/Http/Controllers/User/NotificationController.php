<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->notifications->userNotificationFilters();
        $filter = (string) $request->query('filter', 'all');

        if (! array_key_exists($filter, $filters)) {
            $filter = 'all';
        }

        $types = $this->notifications->typesForFilter($filter);
        $query = $request->user()->userNotifications()->latest();

        if ($types !== null) {
            $query->whereIn('type', $types);
        }

        return view('user.notifications.index', [
            'notifications' => $query->paginate(6)->withQueryString(),
            'filter' => $filter,
            'filters' => $filters,
            'primaryFilters' => array_intersect_key($filters, array_flip(['all', 'account', 'transactions'])),
            'overflowFilters' => array_intersect_key($filters, array_flip(['investments', 'more'])),
            'notifier' => $this->notifications,
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $filters = $this->notifications->userNotificationFilters();
        $filter = (string) $request->input('filter', $request->query('filter', 'all'));

        if (! array_key_exists($filter, $filters)) {
            $filter = 'all';
        }

        $types = $this->notifications->typesForFilter($filter);
        $count = $this->notifications->markAllUserAsRead($request->user(), $types);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'marked' => $count,
                'unread' => 0,
            ]);
        }

        $message = $count > 0
            ? 'All notifications marked as read.'
            : 'No unread notifications to mark.';

        return redirect()
            ->route('user.notifications.index', ['filter' => $filter])
            ->with('status', $message);
    }
}
