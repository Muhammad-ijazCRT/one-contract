@php
    $is = fn (...$patterns) => request()->routeIs(...$patterns);
    $onHome = $is('user.dashboard');
    $onWallet = $is('user.wallets.*', 'user.deposit.*', 'user.deposits.*', 'user.withdraw.*', 'user.transactions.*');
    $onSearch = $is('user.games.*') && request()->filled('q');
    $onChat = $is('user.chat.*');
    $onGames = ($is('user.games.*', 'user.prediction.*', 'user.azubogu.*', 'user.rpsv.*') && ! request()->filled('q'));
    $onBoard = $is('user.leaderboard.*');
    $onRewards = $is('user.rewards.*', 'user.earnings.*');
    $onSettings = $is('user.profile.*', 'user.security.*', 'user.support.*');
@endphp
<aside class="dash-sidebar oc-sb oc-sb--desk" id="dash-sidebar">
    <nav class="oc-sb-nav" aria-label="Main">
        <a href="{{ route('user.dashboard') }}" class="oc-sb-link {{ $onHome ? 'is-active' : '' }}">
            <i class="fa-solid fa-house" aria-hidden="true"></i>
            <span class="oc-sb-label">Home</span>
        </a>
        <a href="{{ route('user.wallets.index') }}" class="oc-sb-link {{ $onWallet ? 'is-active' : '' }}">
            <i class="fa-solid fa-wallet" aria-hidden="true"></i>
            <span class="oc-sb-label">Wallet</span>
        </a>
        <a href="{{ route('user.games.index') }}" class="oc-sb-link {{ $onSearch ? 'is-active' : '' }}">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <span class="oc-sb-label">Search</span>
        </a>
        <a href="{{ route('user.chat.index') }}" class="oc-sb-link {{ $onChat ? 'is-active' : '' }}">
            <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
            <span class="oc-sb-label">Chat</span>
        </a>
        <a href="{{ route('user.games.index') }}" class="oc-sb-link {{ $onGames ? 'is-active' : '' }}">
            <i class="fa-solid fa-gamepad" aria-hidden="true"></i>
            <span class="oc-sb-label">All Games</span>
        </a>
        <a href="{{ route('user.leaderboard.index', ['tab' => 'betting', 'period' => 'week']) }}" class="oc-sb-link {{ $onBoard ? 'is-active' : '' }}">
            <i class="fa-solid fa-chart-simple" aria-hidden="true"></i>
            <span class="oc-sb-label">Leaderboard</span>
        </a>
        <a href="{{ route('user.rewards.claim') }}" class="oc-sb-link {{ $onRewards ? 'is-active' : '' }}">
            <i class="fa-solid fa-gift" aria-hidden="true"></i>
            <span class="oc-sb-label">Rewards</span>
        </a>
        <a href="{{ route('user.profile.edit') }}" class="oc-sb-link {{ $onSettings ? 'is-active' : '' }}">
            <i class="fa-solid fa-gear" aria-hidden="true"></i>
            <span class="oc-sb-label">Settings</span>
        </a>
    </nav>

    <a href="{{ route('user.games.index') }}" class="oc-sb-promo oc-sb-promo--desk">
        <span class="oc-sb-promo-crown" aria-hidden="true">
            <i class="fa-solid fa-crown"></i>
        </span>
        <strong>Play Big.<br>Win Bigger.</strong>
        <small>Real Games. Real People.<br>Real Winnings.</small>
    </a>
</aside>
