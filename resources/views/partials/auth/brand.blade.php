@php
    $variant = $variant ?? 'default';
    $logoFile = public_path('images/logo.png');
    $logoVersion = is_file($logoFile) ? filemtime($logoFile) : time();
@endphp
<div class="oc-onb-brand oc-onb-brand--{{ $variant }}">
    @if ($variant === 'login')
        <div class="oc-login-hero" aria-hidden="false">
            <div class="oc-login-hero-glow"></div>
            <div class="oc-login-hero-ring"></div>
            <a href="{{ $href ?? route('home') }}" class="oc-onb-brand-link" aria-label="ONE CONTRACT Home">
                <img
                    src="{{ asset('images/logo.png') }}?v={{ $logoVersion }}"
                    alt="ONE CONTRACT™"
                    class="oc-login-hero-logo"
                    width="220"
                    height="220"
                    decoding="async"
                >
            </a>
        </div>
    @else
        <a href="{{ $href ?? route('home') }}" class="oc-onb-brand-link" aria-label="ONE CONTRACT Home">
            <img
                src="{{ asset('images/logo.png') }}?v={{ $logoVersion }}"
                alt="ONE CONTRACT™"
                class="oc-onb-brand-logo"
                width="200"
                height="200"
                decoding="async"
            >
        </a>
    @endif
</div>
