<header class="site-header" id="site-header">
    @php
        $isAuthenticated = auth()->check();
        $user = auth()->user();
        $isAdmin = $isAuthenticated && ($user?->role?->value ?? $user?->role) === 'admin';
        $dashboardRoute = $isAdmin ? route('admin.dashboard') : route('user.dashboard');
    @endphp
    <div class="container header-inner">
        @include('partials.logo', ['href' => route('home')])

        <nav class="nav-desktop" aria-label="Main navigation">
            <a href="{{ request()->routeIs('home') ? '#home' : route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
            <a href="{{ route('about') }}" class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}">About Us</a>
            <!-- <a href="{{ route('investment-clubs') }}" class="nav-link {{ request()->routeIs('investment-clubs') ? 'active' : '' }}">Investment Schemes</a>
            <a href="{{ route('savings-plan') }}" class="nav-link {{ request()->routeIs('savings-plan') ? 'active' : '' }}">Savings Plan</a> -->
            <a href="{{ route('how-it-works') }}" class="nav-link {{ request()->routeIs('how-it-works') ? 'active' : '' }}">How It Works</a>
            <a href="{{ route('features') }}" class="nav-link {{ request()->routeIs('features') ? 'active' : '' }}">Features</a>
            <a href="{{ route('faq') }}" class="nav-link {{ request()->routeIs('faq') ? 'active' : '' }}">FAQ</a>
            <a href="{{ route('contact') }}" class="nav-link {{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a>
        </nav>

        <div class="header-actions {{ $isAuthenticated ? 'header-actions--auth' : '' }}">
            @guest
                <a href="{{ route('login') }}" class="btn btn-outline btn-sm">Login</a>
                <a href="{{ route('register') }}" class="btn btn-gold btn-sm">Get Started</a>
            @else
                @include('partials.header-profile-dropdown', [
                    'user' => $user,
                    'dashboardRoute' => $dashboardRoute,
                ])
            @endguest
        </div>

        <button type="button" class="mobile-toggle" id="mobile-toggle" aria-label="Toggle menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>

    <div class="mobile-nav" id="mobile-nav" aria-hidden="true">
        <nav aria-label="Mobile navigation">
            <a href="{{ request()->routeIs('home') ? '#home' : route('home') }}" class="mobile-nav-link">Home</a>
            <a href="{{ route('about') }}" class="mobile-nav-link">About Us</a>
            <a href="{{ route('investment-clubs') }}" class="mobile-nav-link">Investment Schemes</a>
            <a href="{{ route('how-it-works') }}" class="mobile-nav-link">How It Works</a>
            <a href="{{ route('features') }}" class="mobile-nav-link">Features</a>
            <a href="{{ route('faq') }}" class="mobile-nav-link">FAQ</a>
            <a href="{{ route('contact') }}" class="mobile-nav-link">Contact</a>
        </nav>
            <div class="mobile-nav-actions">
                @guest
                    <a href="{{ route('login') }}" class="btn btn-outline btn-block">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-gold btn-block">Get Started</a>
                @endguest
            </div>
    </div>
</header>
