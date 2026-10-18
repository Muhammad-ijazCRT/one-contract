<header class="dash-topbar">
    <div class="dash-topbar-left">
        {{-- Collapse / sidebar toggle temporarily hidden --}}
        <button type="button" class="dash-menu-btn is-temp-hidden" id="dash-menu-btn" aria-label="Toggle sidebar" hidden style="display:none !important;">
            <span></span><span></span><span></span>
        </button>
        <a href="{{ route('user.dashboard') }}" class="dash-topbar-brand" aria-label="ONE CONTRACT Home">
            @php
                $logoFile = public_path('images/logo.png');
                $logoVersion = is_file($logoFile) ? filemtime($logoFile) : time();
            @endphp
            <img
                src="{{ asset('images/logo.png') }}?v={{ $logoVersion }}"
                alt="ONE CONTRACT™"
                class="dash-topbar-logo oc-brand-logo"
                width="120"
                height="120"
                decoding="async"
            >
        </a>
        {{-- Page title moved into dash-content (.oc-page-head) — not shown in header --}}
        <div class="dash-topbar-titles is-temp-hidden" hidden aria-hidden="true" style="display:none !important;">
            <h1 class="dash-page-title">@yield('page_title', 'Dashboard')</h1>
            <p class="dash-greeting">Welcome, {{ auth()->user()->name }}</p>
        </div>
    </div>

    <form class="dash-top-search" action="{{ route('user.games.index') }}" method="get" role="search">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            placeholder="Search games, players, or anything..."
            aria-label="Search games, players, or anything"
            autocomplete="off"
        >
    </form>

    @php
        $headerCurrency = $header_wallet_currency ?? auth()->user()?->preferredWalletCurrency();
        $headerBalance = (float) ($header_wallet_balance ?? 0);
        $headerFormatted = $header_wallet_formatted ?? ($headerCurrency?->format($headerBalance) ?? '$0.00');
    @endphp

    <div class="dash-top-balance" data-dash-top-balance>
        <div class="dash-top-balance-main">
            <button type="button" class="dash-top-balance-text" data-oc-currency-open title="Select gaming currency">
                <span class="dash-top-balance-amount-row">
                    <i class="fa-solid fa-wallet dash-top-balance-wallet" aria-hidden="true"></i>
                    <strong
                        class="dash-top-balance-value"
                        data-dash-balance-value
                        data-balance-visible="{{ $headerFormatted }}"
                        data-balance-hidden="••••••"
                    >{{ $headerFormatted }}</strong>
                    <span class="dash-top-balance-code" data-dash-balance-code>{{ $headerCurrency?->value }}</span>
                    <i class="fa-solid fa-chevron-down dash-top-balance-chevron" aria-hidden="true"></i>
                </span>
            </button>
            <a
                href="{{ route('user.deposit.index', ['currency' => ($headerCurrency?->isCrypto() ? $headerCurrency->value : 'USDT')]) }}"
                class="dash-top-balance-add"
                aria-label="Add funds"
                title="Deposit"
            >
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    <div class="dash-topbar-right">
        <a
            href="{{ route('user.rewards.claim') }}"
            class="dash-icon-btn dash-gift-btn {{ request()->routeIs('user.rewards.*') ? 'is-active' : '' }}"
            aria-label="Rewards"
            title="Rewards"
        >
            <i class="fa-solid fa-gift" aria-hidden="true"></i>
        </a>

        <a
            href="{{ route('user.chat.index') }}"
            class="dash-icon-btn dash-chat-btn {{ request()->routeIs('user.chat.*') ? 'is-active' : '' }}"
            aria-label="Open chat"
            title="Chat"
            data-dash-chat-btn
        >
            <i class="fa-solid fa-comments" aria-hidden="true"></i>
            @php $chatBadge = (int) ($chat_unread_count ?? 0); @endphp
            <span class="dash-chat-badge" data-dash-chat-badge @if ($chatBadge <= 0) hidden @endif>{{ $chatBadge > 9 ? '9+' : $chatBadge }}</span>
        </a>

        @include('partials.notification-dropdown', [
            'markReadUrl' => route('user.notifications.mark-all'),
        ])

        <button type="button" class="dash-icon-btn" id="fullscreen-btn" aria-label="Toggle fullscreen">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
        </button>

        <div class="user-dropdown" id="user-dropdown">
            <button type="button" class="dash-user user-dropdown-toggle" id="user-dropdown-toggle" aria-expanded="false" aria-haspopup="true">
                @if (auth()->user()->avatarUrl())
                    <img src="{{ auth()->user()->avatarUrl() }}?v={{ auth()->user()->updated_at?->timestamp }}" alt="" class="dash-avatar dash-avatar-img">
                @else
                    <div class="dash-avatar dash-avatar--default" aria-hidden="true">
                        <i class="fa-solid fa-user"></i>
                    </div>
                @endif
                <div class="dash-user-info">
                    <span class="dash-user-name">{{ auth()->user()->name }}</span>
                    <span class="dash-user-id">{{ auth()->user()->member_id }}</span>
                </div>
                <svg class="user-dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="user-dropdown-menu" id="user-dropdown-menu" role="menu">
                <a href="{{ route('user.profile.edit') }}" class="user-dropdown-item" role="menuitem">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    Profile
                </a>
                <a href="{{ route('user.profile.edit', ['tab' => 'security']) }}" class="user-dropdown-item" role="menuitem">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    Security
                </a>
                <div class="user-dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}" class="user-dropdown-logout-form">
                    @csrf
                    <button type="submit" class="user-dropdown-item user-dropdown-logout" role="menuitem">
                        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
