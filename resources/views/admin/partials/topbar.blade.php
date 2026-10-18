<header class="dash-topbar admin-topbar">
    <div class="dash-topbar-left">
        <button type="button" class="dash-menu-btn" id="dash-menu-btn" aria-label="Toggle sidebar">
            <span></span><span></span><span></span>
        </button>
        <div class="dash-topbar-titles">
            <h1 class="dash-page-title">@yield('page_title', 'Admin')</h1>
            <p class="dash-greeting">Welcome back, {{ auth()->user()->name }}</p>
        </div>
    </div>
    <div class="dash-topbar-right">
        <form class="admin-search" action="#" method="GET" role="search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input type="search" name="q" placeholder="Search users, deposits..." aria-label="Search">
        </form>

        @include('partials.notification-dropdown', [
            'markReadUrl' => route('admin.notifications.mark-all'),
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
                    <span class="dash-user-id">Super Administrator</span>
                </div>
                <svg class="user-dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div class="user-dropdown-menu" id="user-dropdown-menu" role="menu">
                <a href="{{ route('admin.profile.edit') }}" class="user-dropdown-item" role="menuitem">
                    <i class="fa-solid fa-user" aria-hidden="true"></i>
                    Profile
                </a>
                <a href="{{ route('admin.profile.edit', ['tab' => 'security']) }}" class="user-dropdown-item" role="menuitem">
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
