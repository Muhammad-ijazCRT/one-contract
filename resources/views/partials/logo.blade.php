@php
    $logoFile = public_path('images/logo.png');
    $logoVersion = is_file($logoFile) ? filemtime($logoFile) : time();
    $logoSrc = asset('images/logo.png').'?v='.$logoVersion;
@endphp
<a href="{{ $href ?? route('home') }}" class="logo {{ $class ?? '' }}">
    <img
        src="{{ $logoSrc }}"
        alt="ONE CONTRACT™"
        class="logo-img logo-img--official oc-brand-logo"
        width="120"
        height="120"
        decoding="async"
    >
</a>
