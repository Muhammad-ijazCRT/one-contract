@extends('layouts.dashboard')

@section('title', 'Notifications — ONE CONTRACT™')
@section('page_title', 'Notifications')

@push('styles')
    @vite('resources/css/user-notifications.css')
@endpush

@section('content')
<div class="oc-notif-page">
    <header class="oc-notif-head">
        <div class="oc-notif-head-copy">
            <h1>Notifications</h1>
            <p>Stay updated with what’s happening on One Contract.</p>
        </div>

        <form method="POST" action="{{ route('user.notifications.mark-all') }}">
            @csrf
            <input type="hidden" name="filter" value="{{ $filter }}">
            <button type="submit" class="oc-notif-mark-all">
                Mark all as read
                <i class="fa-solid fa-check" aria-hidden="true"></i>
            </button>
        </form>
    </header>

    @if (session('status'))
        <p class="oc-notif-flash" role="status">{{ session('status') }}</p>
    @endif

    <nav class="oc-notif-tabs" aria-label="Notification filters">
        <div class="oc-notif-tabs-row oc-notif-tabs-row--desktop">
            @foreach ($filters as $key => $tab)
                <a
                    href="{{ route('user.notifications.index', ['filter' => $key]) }}"
                    class="oc-notif-tab {{ $filter === $key ? 'is-active' : '' }}"
                >
                    <i class="{{ $tab['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $tab['label'] }}</span>
                </a>
            @endforeach
        </div>

        @php($moreActive = array_key_exists($filter, $overflowFilters))

        <div class="oc-notif-tabs-row oc-notif-tabs-row--mobile">
            @foreach ($primaryFilters as $key => $tab)
                <a
                    href="{{ route('user.notifications.index', ['filter' => $key]) }}"
                    class="oc-notif-tab {{ $filter === $key ? 'is-active' : '' }}"
                >
                    <i class="{{ $tab['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $tab['label'] }}</span>
                </a>
            @endforeach

            <div class="oc-notif-more" data-notif-more>
                <button
                    type="button"
                    class="oc-notif-tab oc-notif-more-btn {{ $moreActive ? 'is-active' : '' }}"
                    aria-expanded="false"
                    aria-haspopup="true"
                >
                    <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
                    <span>More</span>
                </button>

                <div class="oc-notif-more-menu" hidden>
                    @foreach ($overflowFilters as $key => $tab)
                        <a
                            href="{{ route('user.notifications.index', ['filter' => $key]) }}"
                            class="oc-notif-more-item {{ $filter === $key ? 'is-active' : '' }}"
                        >
                            <i class="{{ $tab['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $tab['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </nav>

    <div class="oc-notif-list">
        @forelse ($notifications as $item)
            @php($meta = $notifier->presentationForType($item->type))
            <a
                href="{{ route('user.notifications.read', $item) }}"
                class="oc-notif-item {{ $item->read_at ? '' : 'is-unread' }}"
            >
                <span class="oc-notif-icon oc-notif-icon--{{ $meta['tone'] }}" aria-hidden="true">
                    <i class="{{ $meta['icon'] }}"></i>
                </span>

                <div class="oc-notif-copy">
                    <strong>{{ $item->title }}</strong>
                    <p>{{ $item->message }}</p>
                </div>

                <div class="oc-notif-meta">
                    <time datetime="{{ $item->created_at?->toIso8601String() }}">
                        {{ $notifier->formatUserNotificationTime($item->created_at) }}
                    </time>
                    <span class="oc-notif-dot {{ $item->read_at ? '' : 'is-unread' }}" aria-hidden="true"></span>
                    <i class="fa-solid fa-chevron-right oc-notif-chevron" aria-hidden="true"></i>
                </div>
            </a>
        @empty
            <div class="oc-notif-empty">
                <i class="fa-regular fa-bell-slash" aria-hidden="true"></i>
                <p>No notifications in this category yet.</p>
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="oc-notif-pagination">
            {{ $notifications->links('vendor.pagination.notifications') }}
        </div>
    @elseif ($notifications->total() > 0)
        <p class="oc-notif-count">
            Showing <strong>{{ $notifications->firstItem() }}</strong> to <strong>{{ $notifications->lastItem() }}</strong> of <strong>{{ $notifications->total() }}</strong> notifications
        </p>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const more = document.querySelector('[data-notif-more]');
    if (!more) return;

    const btn = more.querySelector('.oc-notif-more-btn');
    const menu = more.querySelector('.oc-notif-more-menu');
    if (!btn || !menu) return;

    const close = () => {
        menu.hidden = true;
        btn.setAttribute('aria-expanded', 'false');
        more.classList.remove('is-open');
    };

    btn.addEventListener('click', (event) => {
        event.stopPropagation();
        const open = menu.hidden;
        close();
        if (open) {
            menu.hidden = false;
            btn.setAttribute('aria-expanded', 'true');
            more.classList.add('is-open');
        }
    });

    document.addEventListener('click', (event) => {
        if (!more.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
});
</script>
@endpush
