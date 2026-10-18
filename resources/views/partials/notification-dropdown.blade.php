@php
    $notifications = $notifications ?? ['count' => 0, 'items' => collect()];
    $notificationCount = (int) ($notifications['count'] ?? 0);
@endphp
<div class="notification-dropdown" id="notification-dropdown" @if (! empty($markReadUrl)) data-mark-read-url="{{ $markReadUrl }}" @endif>
    <button
        type="button"
        class="dash-icon-btn notification-dropdown-toggle"
        id="notification-dropdown-toggle"
        aria-expanded="false"
        aria-haspopup="true"
        aria-label="Notifications"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        @if ($notificationCount > 0)
            <span class="badge" id="notification-badge">{{ $notificationCount > 9 ? '9+' : $notificationCount }}</span>
        @endif
    </button>
    <div class="notification-dropdown-panel" id="notification-dropdown-panel" role="menu">
        <div class="notification-dropdown-header">
            <strong>Notifications</strong>
            @if ($notificationCount > 0)
                <span class="notification-dropdown-count" id="notification-header-count">{{ $notificationCount }} new</span>
            @endif
        </div>
        <div class="notification-dropdown-list">
            @forelse ($notifications['items'] as $item)
                <a
                    href="{{ $item['url'] ?? '#' }}"
                    class="notification-dropdown-item notification-dropdown-link {{ !empty($item['unread']) ? 'unread' : '' }}"
                    role="menuitem"
                >
                    <div class="notification-dropdown-item-top">
                        <div class="notification-dropdown-item-head">
                            <span class="notification-dot {{ $item['type'] ?? 'default' }}"></span>
                            <strong>{{ $item['title'] }}</strong>
                        </div>
                        <span class="notification-dropdown-time">{{ $item['time']->diffForHumans() }}</span>
                    </div>
                    <p>{{ $item['message'] }}</p>
                </a>
            @empty
                <p class="notification-dropdown-empty">No notifications yet</p>
            @endforelse
        </div>
        <a href="{{ route('user.notifications.index') }}" class="notification-dropdown-footer">View All</a>
    </div>
</div>
