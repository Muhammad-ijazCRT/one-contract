<aside class="dash-sidebar dash-sidebar-admin" id="dash-sidebar">
    <div class="dash-sidebar-top">
        <div class="admin-logo-wrap">
            @include('partials.logo', ['href' => route('admin.dashboard'), 'class' => 'dash-logo'])
            <p class="admin-badge">Admin Panel</p>
        </div>

        @php $navCounts = $admin_nav_counts ?? []; @endphp
        <nav class="dash-nav admin-nav">
            <a href="{{ route('admin.dashboard') }}" class="dash-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-house fa-fw"></i>
                Dashboard
            </a>
            <a href="{{ route('admin.users.index') }}" class="dash-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="fa-solid fa-users fa-fw"></i>
                <span>Users</span>
                @if (($navCounts['users'] ?? 0) > 0)
                    <span class="admin-nav-count">{{ $navCounts['users'] }}</span>
                @endif
            </a>
            <!-- Users moved into Settings submenu -->
            <a href="{{ route('admin.deposits.index') }}" class="dash-nav-link {{ request()->routeIs('admin.deposits.*') ? 'active' : '' }}">
                <i class="fa-solid fa-wallet fa-fw"></i>
                <span>Deposits</span>
                @if (($navCounts['deposits'] ?? 0) > 0)
                    <span class="admin-nav-count">{{ $navCounts['deposits'] }}</span>
                @endif
            </a>
            <div class="admin-nav-group {{ request()->routeIs('admin.withdrawals.*') ? 'open' : '' }}">
                    <button type="button" class="dash-nav-link admin-nav-toggle {{ request()->routeIs('admin.withdrawals.*') ? 'active' : '' }}" data-nav-toggle>
                    <span class="admin-nav-label">
                        <i class="fa-solid fa-arrow-up-right-from-square fa-fw"></i>
                        Withdrawals
                    </span>
                    <span class="admin-nav-toggle-meta">
                        @if (($navCounts['withdrawals'] ?? 0) > 0)
                            <span class="admin-nav-count">{{ $navCounts['withdrawals'] }}</span>
                        @endif
                        <svg class="admin-nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </span>
                </button>
                <div class="admin-nav-submenu">
                    <a href="{{ route('admin.withdrawals.index') }}" class="dash-nav-link sub {{ request()->routeIs('admin.withdrawals.index') && ! request('type') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/><circle cx="18" cy="17" r="1.3"/></svg>
                        All Withdrawals
                    </a>
                    <a href="{{ route('admin.withdrawals.reward') }}" class="dash-nav-link sub {{ request()->routeIs('admin.withdrawals.reward') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18"/><path d="M17 6H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        <span>Main Wallet</span>
                        @if (($navCounts['withdrawals_reward'] ?? 0) > 0)
                            <span class="admin-nav-count">{{ $navCounts['withdrawals_reward'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.withdrawals.capital') }}" class="dash-nav-link sub {{ request()->routeIs('admin.withdrawals.capital') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M7 12h10"/><path d="M12 8v8"/></svg>
                        <span>Capital</span>
                        @if (($navCounts['withdrawals_capital'] ?? 0) > 0)
                            <span class="admin-nav-count">{{ $navCounts['withdrawals_capital'] }}</span>
                        @endif
                    </a>
                </div>
            </div>

            <a href="{{ route('admin.rewards.index') }}" class="dash-nav-link {{ request()->routeIs('admin.rewards.*') ? 'active' : '' }}">
                <i class="fa-solid fa-gift fa-fw"></i>
                Rewards
            </a>

            <div class="admin-nav-group {{ request()->routeIs('admin.clubs.*') || request()->routeIs('admin.capital-requests.*') || request()->routeIs('admin.savings.*') ? 'open' : '' }}">
                <button type="button" class="dash-nav-link admin-nav-toggle {{ request()->routeIs('admin.clubs.*') || request()->routeIs('admin.capital-requests.*') || request()->routeIs('admin.savings.*') ? 'active' : '' }}" data-nav-toggle>
                    <span class="admin-nav-label">
                        <i class="fa-solid fa-layer-group fa-fw"></i>
                        Dual Wallet
                    </span>
                    <span class="admin-nav-toggle-meta">
                        @if (($navCounts['dual_wallet'] ?? 0) > 0)
                            <span class="admin-nav-count">{{ $navCounts['dual_wallet'] }}</span>
                        @endif
                        <svg class="admin-nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                    </span>
                </button>
                <div class="admin-nav-submenu">
                    <a href="{{ route('admin.clubs.index') }}" class="dash-nav-link sub {{ request()->routeIs('admin.clubs.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-chart-pie fa-fw"></i>
                        Investment Schemes
                    </a>
                    <a href="{{ route('admin.capital-requests.index') }}" class="dash-nav-link sub {{ request()->routeIs('admin.capital-requests.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-hourglass-half fa-fw"></i>
                        <span>Capital Returns</span>
                        @if (($navCounts['capital_returns'] ?? 0) > 0)
                            <span class="admin-nav-count">{{ $navCounts['capital_returns'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.savings.index') }}" class="dash-nav-link sub {{ request()->routeIs('admin.savings.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-piggy-bank fa-fw"></i>
                        <span>Savings Plans</span>
                        @if (($navCounts['savings'] ?? 0) > 0)
                            <span class="admin-nav-count">{{ $navCounts['savings'] }}</span>
                        @endif
                    </a>
                </div>
            </div>

            <a href="{{ route('admin.referrals.index') }}" class="dash-nav-link {{ request()->routeIs('admin.referrals.*') ? 'active' : '' }}">
                <i class="fa-solid fa-user-plus fa-fw"></i>
                Referrals
            </a>
            <!-- Support is top-level; Settings contains Admin Users & System Logs -->
            <a href="{{ route('admin.transactions.index') }}" class="dash-nav-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                <i class="fa-solid fa-exchange-alt fa-fw"></i>
                Transactions
            </a>
            <a href="{{ route('admin.treasury.index') }}" class="dash-nav-link {{ request()->routeIs('admin.treasury.*') ? 'active' : '' }}">
                <i class="fa-solid fa-building-columns fa-fw"></i>
                Treasury
            </a>

            <div class="admin-nav-group {{ request()->routeIs('admin.reports.*') ? 'open' : '' }}">
                <button type="button" class="dash-nav-link admin-nav-toggle {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" data-nav-toggle>
                    <span class="admin-nav-label">
                        <i class="fa-solid fa-chart-line fa-fw"></i>
                        Reports
                    </span>
                    <svg class="admin-nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="admin-nav-submenu">
                    <a href="{{ route('admin.reports.earnings') }}" class="dash-nav-link sub {{ request()->routeIs('admin.reports.earnings') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 19h18"/><path d="M6 15l4-5 4 3 5-8"/><circle cx="6" cy="15" r="1.2"/></svg>
                        Earnings Report
                    </a>
                    <a href="{{ route('admin.reports.withdrawals') }}" class="dash-nav-link sub {{ request()->routeIs('admin.reports.withdrawals') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M7 10l5-5 5 5"/></svg>
                        Withdrawal Report
                    </a>
                </div>
            </div>

            <a href="{{ route('admin.announcements.index') }}" class="dash-nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}">
                <i class="fa-solid fa-bullhorn fa-fw"></i>
                Announcements
            </a>
            <a href="{{ route('admin.support.index') }}" class="dash-nav-link {{ request()->routeIs('admin.support.*') ? 'active' : '' }}">
                <i class="fa-solid fa-ticket fa-fw"></i>
                <span>Support Tickets</span>
                @if (($navCounts['support'] ?? 0) > 0)
                    <span class="admin-nav-count">{{ $navCounts['support'] }}</span>
                @endif
            </a>
            <a href="{{ route('admin.contact-settings.index') }}" class="dash-nav-link {{ request()->routeIs('admin.contact-settings.*') ? 'active' : '' }}">
                <i class="fa-solid fa-address-book fa-fw"></i>
                Contact Settings
            </a>
            <a href="{{ route('admin.newsletter.index') }}" class="dash-nav-link {{ request()->routeIs('admin.newsletter.*') ? 'active' : '' }}">
                <i class="fa-solid fa-envelope-open-text fa-fw"></i>
                Newsletter
            </a>

            <div class="admin-nav-group {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.payment-settings.*') || request()->routeIs('admin.kyc-settings.*') || request()->routeIs('admin.prediction-trends.*') || request()->routeIs('admin.logs.*') ? 'open' : '' }}">
                <button type="button" class="dash-nav-link admin-nav-toggle {{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.payment-settings.*') || request()->routeIs('admin.kyc-settings.*') || request()->routeIs('admin.prediction-trends.*') || request()->routeIs('admin.logs.*') ? 'active' : '' }}" data-nav-toggle>
                    <span class="admin-nav-label">
                        <i class="fa-solid fa-cog fa-fw"></i>
                        Settings
                    </span>
                    <svg class="admin-nav-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="admin-nav-submenu">
                    <a href="{{ route('admin.settings.index') }}" class="dash-nav-link sub {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-sliders fa-fw"></i>
                        Platform Settings
                    </a>
                    <a href="{{ route('admin.payment-settings.index') }}" class="dash-nav-link sub {{ request()->routeIs('admin.payment-settings.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-credit-card fa-fw"></i>
                        Payment Settings
                    </a>
                    <a href="{{ route('admin.kyc-settings.index') }}" class="dash-nav-link sub {{ request()->routeIs('admin.kyc-settings.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-id-card fa-fw"></i>
                        KYC Settings
                    </a>
                    <a href="{{ route('admin.prediction-trends.index') }}" class="dash-nav-link sub {{ request()->routeIs('admin.prediction-trends.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-wave-square fa-fw"></i>
                        Prediction Trends
                    </a>
                    <a href="{{ route('admin.logs.index') }}" class="dash-nav-link sub {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9z"/><path d="M14 2v7h7"/></svg>
                        System Logs
                    </a>
                </div>
            </div>
            
        </nav>
    </div>

    <div class="dash-sidebar-bottom admin-sidebar-bottom">
        @php
            $treasuryHealthClass = match ($treasury['health'] ?? 'Healthy') {
                'Healthy' => 'healthy',
                'Moderate' => 'moderate',
                default => 'low',
            };
            $currencySymbol = $treasury['currency_symbol'] ?? '$';
            $currencyCode = $treasury['currency_code'] ?? 'USDT';
            $liquidityPercent = min(100, max(0, $treasury['capacity_percent'] ?? 0));
        @endphp
        <a href="{{ route('admin.treasury.index') }}" class="admin-treasury-card admin-treasury-card-link">
            <div class="admin-treasury-section">
                <span class="admin-treasury-label">Treasury Balance</span>
                <div class="admin-treasury-balance-row">
                    <strong class="admin-treasury-value">
                        {{ $currencySymbol }}{{ number_format($treasury['balance'] ?? 0, 2) }}
                        <small>{{ $currencyCode }}</small>
                    </strong>
                    <span class="admin-treasury-badge {{ $treasuryHealthClass }}">{{ $treasury['health'] ?? 'Healthy' }}</span>
                </div>
            </div>

            <div class="admin-treasury-section">
                <span class="admin-treasury-label">Available Liquidity</span>
                <strong class="admin-treasury-value admin-treasury-value--liquidity">
                    {{ $currencySymbol }}{{ number_format($treasury['available_liquidity'] ?? 0, 2) }}
                    <small>{{ $currencyCode }}</small>
                </strong>
                <div class="admin-treasury-progress">
                    <div class="admin-treasury-bar">
                        <div class="admin-treasury-bar-fill" style="width: {{ $liquidityPercent }}%"></div>
                    </div>
                    <span class="admin-treasury-percent">{{ number_format($liquidityPercent, 1) }}%</span>
                </div>
            </div>
        </a>
    </div>
</aside>


