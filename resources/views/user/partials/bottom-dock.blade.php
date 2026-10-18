@php
    $dockLogoPath = is_file(public_path('images/logo-icon.png')) ? 'images/logo-icon.png' : 'images/logo.png';
    $dockLogoFile = public_path($dockLogoPath);
    $dockLogo = asset($dockLogoPath).'?v='.(is_file($dockLogoFile) ? filemtime($dockLogoFile) : time());

    $is = fn (...$patterns) => request()->routeIs(...$patterns);

    $onWallet = $is('user.wallets.*', 'user.deposit.*', 'user.deposits.*', 'user.withdraw.*', 'user.transactions.*');
    $onInvest = $is('user.clubs.*', 'user.referrals.*', 'user.lending.*');
    $onReward = $is('user.rewards.*', 'user.earnings.*');
    $onAccount = $is('user.profile.*', 'user.security.*', 'user.support.*', 'user.notifications.*');
    $onGames = $is('user.games.*', 'user.prediction.*', 'user.azubogu.*', 'user.rpsv.*');
    $onHome = $is('user.dashboard', 'user.leaderboard.*');

    if ($onWallet) {
        $dockMode = 'wallet';
    } elseif ($onInvest) {
        $dockMode = 'invest';
    } elseif ($onReward) {
        $dockMode = 'reward';
    } elseif ($onGames) {
        $dockMode = 'games';
    } elseif ($onAccount) {
        $dockMode = 'account';
    } else {
        $dockMode = 'main';
    }

    $item = function (string $url, string $icon, string $label, bool $active) {
        return compact('url', 'icon', 'label', 'active');
    };

    $center = function (string $url, string $label, bool $active) use ($dockLogo) {
        return [
            'type' => 'center',
            'url' => $url,
            'label' => $label,
            'active' => $active,
            'logo' => $dockLogo,
        ];
    };

    $docks = match ($dockMode) {
        'wallet' => [
            $item(route('user.wallets.index'), 'fa-wallet', 'WALLET', $is('user.wallets.*', 'user.transactions.*')),
            $item(route('user.deposit.index'), 'fa-arrow-down', 'DEPOSIT', $is('user.deposit.*', 'user.deposits.*')),
            $center(route('user.dashboard'), 'HOME', false),
            $item(route('user.withdraw.index'), 'fa-arrow-up', 'WITHDRAW', $is('user.withdraw.*')),
        ],
        'invest' => [
            $item(route('user.clubs.index'), 'fa-chart-column', 'SCHEME', $is('user.clubs.*')),
            $item(route('user.lending.index'), 'fa-hand-holding-dollar', 'LEND', $is('user.lending.*')),
            $center(route('user.clubs.index'), 'SCHEME', $is('user.clubs.*')),
            $item(route('user.referrals.index'), 'fa-users', 'REFERRAL', $is('user.referrals.*')),
            $item(route('user.dashboard'), 'fa-house', 'HOME', false),
        ],
        'games' => [
            $item(route('user.rpsv.index'), 'fa-hand-back-fist', 'RPSV', $is('user.rpsv.*')),
            $item(route('user.prediction.index'), 'fa-bolt', 'PREDICT', $is('user.prediction.*')),
            $center(route('user.games.index'), 'GAMES', $is('user.games.*')),
            $item(route('user.dashboard'), 'fa-house', 'HOME', false),
        ],
        'reward' => [
            $item(route('user.dashboard'), 'fa-house', 'HOME', false),
            $item(route('user.rewards.claim'), 'fa-gift', 'REWARD', $is('user.rewards.*')),
            $center(route('user.games.index'), 'SEARCH', false),
            $item(route('user.earnings.index'), 'fa-chart-pie', 'EARNINGS', $is('user.earnings.*')),
            $item(route('user.wallets.index'), 'fa-wallet', 'WALLET', false),
        ],
        'account' => [
            $item(route('user.dashboard'), 'fa-house', 'HOME', false),
            $item(route('user.profile.edit'), 'fa-user', 'PROFILE', $is('user.profile.*', 'user.security.*')),
            $center(route('user.games.index'), 'SEARCH', false),
            $item(route('user.support.index'), 'fa-headset', 'SUPPORT', $is('user.support.*')),
            $item(route('user.notifications.index'), 'fa-bell', 'ALERTS', $is('user.notifications.*')),
        ],
        default => [
            $item(route('user.dashboard'), 'fa-house', 'HOME', $onHome || $is('user.leaderboard.*')),
            $item(route('user.wallets.index'), 'fa-wallet', 'WALLET', false),
            $center(route('user.games.index'), 'SEARCH', false),
            $item(route('user.chat.index'), 'fa-comment-dots', 'CHAT', $is('user.chat.*')),
        ],
    };

    if ($is('user.chat.*')) {
        $dockMode = 'main';
        $docks = [
            $item(route('user.dashboard'), 'fa-house', 'HOME', false),
            $item(route('user.wallets.index'), 'fa-wallet', 'WALLET', false),
            $center(route('user.games.index'), 'SEARCH', false),
            $item(route('user.chat.index'), 'fa-comment-dots', 'CHAT', true),
        ];
    }
@endphp
<nav class="och4-dock och4-dock--{{ $dockMode }} och4-dock--count-{{ count($docks) }}" aria-label="Primary" data-dock-mode="{{ $dockMode }}">
    @foreach ($docks as $tab)
        @if (($tab['type'] ?? '') === 'center')
            <a href="{{ $tab['url'] }}" class="och4-dock-center {{ $tab['active'] ? 'is-active' : '' }} {{ ($tab['label'] ?? '') === 'SEARCH' ? 'och4-dock-center--search' : '' }}" aria-label="{{ $tab['label'] }}">
                <span class="och4-dock-orb">
                    @if (($tab['label'] ?? '') === 'SEARCH')
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    @else
                        <img src="{{ $tab['logo'] }}" alt="ONE CONTRACT™" class="oc-dock-logo" decoding="async">
                    @endif
                </span>
                <small>{{ $tab['label'] }}</small>
            </a>
        @else
            <a href="{{ $tab['url'] }}" class="och4-dock-item {{ $tab['active'] ? 'is-active' : '' }}">
                <i class="fa-solid {{ $tab['icon'] }}"></i>
                <small>{{ $tab['label'] }}</small>
            </a>
        @endif
    @endforeach
</nav>
