@php
    $authDash = auth()->check();
    $isAdmin = $authDash && (auth()->user()?->role?->value ?? auth()->user()?->role) === 'admin';
    $dashRoute = $isAdmin ? route('admin.dashboard') : route('user.dashboard');
    $startRoute = $authDash ? $dashRoute : route('register');
    $loginRoute = route('login');

    $features = [
        [
            'tone' => 'lime',
            'title' => 'Original Games',
            'text' => 'Play our own exclusive games',
            'icon' => 'gamepad',
        ],
        [
            'tone' => 'gold',
            'title' => 'Tournaments',
            'text' => 'Compete and win amazing rewards',
            'icon' => 'trophy',
        ],
        [
            'tone' => 'lime',
            'title' => 'Promotions',
            'text' => 'Exciting offers and bonuses',
            'icon' => 'gift',
        ],
        [
            'tone' => 'lime',
            'title' => 'Secure & Fair',
            'text' => 'Advanced technology you can trust',
            'icon' => 'shield',
        ],
    ];
@endphp

<section class="oc-home-onb" id="home" aria-label="ONE CONTRACT">
    <div class="oc-home-onb-inner">
        <img
            class="oc-home-onb-hero"
            src="{{ asset('images/landing/hero.png') }}"
            alt="ONE CONTRACT. World smartest iGaming platform."
            width="1024"
            height="318"
            decoding="async"
        >

        <header class="oc-home-onb-copy">
            <h1>A Smarter Way <em>to Play</em></h1>
            <p>Unique games. Real rewards. A next generation<br>iGaming platform built for you.</p>
        </header>

        <ul class="oc-home-onb-cards">
            @foreach ($features as $feature)
                <li>
                    <a href="{{ $startRoute }}" class="oc-home-onb-card">
                        <span class="oc-home-onb-card-icon oc-home-onb-card-icon--{{ $feature['tone'] }}" aria-hidden="true">
                            @if ($feature['icon'] === 'gamepad')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M8.1 8.2h7.8c2.3 0 4 1.6 4 3.9 0 2.5-1.5 4.4-3.4 4.4-1 0-1.7-.6-2.4-1.5-.5-.7-1.1-1.1-1.9-1.1s-1.4.4-1.9 1.1c-.7.9-1.4 1.5-2.4 1.5-1.9 0-3.4-1.9-3.4-4.4 0-2.3 1.7-3.9 4-3.9z"/>
                                    <path d="M8.4 11.1v2.5M7.15 12.35h2.5"/>
                                    <circle cx="15.15" cy="11.15" r="0.7" fill="currentColor" stroke="none"/>
                                    <circle cx="16.95" cy="12.85" r="0.7" fill="currentColor" stroke="none"/>
                                </svg>
                            @elseif ($feature['icon'] === 'trophy')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M8 4.2h8v5.6a4 4 0 0 1-8 0V4.2z"/>
                                    <path d="M8 6.2H5.6a2.4 2.4 0 0 0 2.2 4"/>
                                    <path d="M16 6.2h2.4a2.4 2.4 0 0 1-2.2 4"/>
                                    <path d="M12 13.8v2.4"/>
                                    <path d="M9.2 19.6h5.6"/>
                                    <path d="M10 16.2h4v3.4h-4z"/>
                                </svg>
                            @elseif ($feature['icon'] === 'gift')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="4.2" y="10" width="15.6" height="9.2" rx="1.3"/>
                                    <path d="M4.2 14.2h15.6"/>
                                    <path d="M12 10v9.2"/>
                                    <path d="M12 10c0 0-1.1-5.4-4.1-4.2S8.6 10 12 10c0 0 1.1-5.4 4.1-4.2S15.4 10 12 10z"/>
                                </svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 3.4 18.8 6v5.3c0 4-2.6 6.9-6.8 8.6-4.2-1.7-6.8-4.6-6.8-8.6V6L12 3.4z"/>
                                    <path d="m8.7 11.9 2.2 2.2 4.2-4.3"/>
                                </svg>
                            @endif
                        </span>
                        <strong>{{ $feature['title'] }}</strong>
                        <span>{{ $feature['text'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <figure class="oc-home-onb-chip">
            <div class="oc-home-onb-chip-frame">
                <img
                    src="{{ asset('images/landing/chip.jpg') }}"
                    alt=""
                    width="1024"
                    height="254"
                    decoding="async"
                >
            </div>
            <div class="oc-home-onb-dots" aria-hidden="true">
                <span class="is-active"></span>
                <span></span>
                <span></span>
            </div>
        </figure>

        <a href="{{ $startRoute }}" class="oc-home-onb-cta">
            Get Started
            <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h12"/>
                <path d="m13 6 6 6-6 6"/>
            </svg>
        </a>

        <p class="oc-home-onb-foot">
            Already have an account? <a href="{{ $loginRoute }}">Log in</a>
        </p>
    </div>
</section>
