<div class="header-profile-dropdown" id="header-profile-dropdown">
    <button
        type="button"
        class="header-profile-toggle"
        id="header-profile-toggle"
        aria-expanded="false"
        aria-haspopup="true"
        aria-label="Account menu"
    >
        @if ($user->avatarUrl())
            <img
                src="{{ $user->avatarUrl() }}?v={{ $user->updated_at?->timestamp }}"
                alt=""
                class="header-profile-avatar header-profile-avatar-img"
            >
        @else
            <span class="header-profile-avatar header-profile-avatar--default" aria-hidden="true">
                <i class="fa-solid fa-user"></i>
            </span>
        @endif
        <span class="header-profile-name">{{ $user->name }}</span>
        <i class="fa-solid fa-chevron-down header-profile-chevron" aria-hidden="true"></i>
    </button>

    <div class="header-profile-menu" id="header-profile-menu" role="menu">
        <a href="{{ $dashboardRoute }}" class="header-profile-item" role="menuitem">
            <i class="fa-solid fa-table-cells" aria-hidden="true"></i>
            Dashboard
        </a>
        <form method="POST" action="{{ route('logout') }}" class="header-profile-logout-form">
            @csrf
            <button type="submit" class="header-profile-item header-profile-logout" role="menuitem">
                <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                Logout
            </button>
        </form>
    </div>
</div>
