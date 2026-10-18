@php
    $desktop = $desktop ?? '';
    $mobile = $mobile ?? $desktop;
    $alt = $alt ?? '';
    $eager = ! empty($eager);
    $defer = ! empty($defer);
    $width = $width ?? 1920;
    $height = $height ?? 1080;
    $placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
@endphp
<picture>
    <source
        media="(max-width: 767px)"
        @if($defer)
            data-srcset="{{ $mobile }}"
        @else
            srcset="{{ $mobile }}"
        @endif
    >
    @if($defer)
        <img
            src="{{ $placeholder }}"
            data-src="{{ $desktop }}"
            alt="{{ $alt }}"
            width="{{ $width }}"
            height="{{ $height }}"
            loading="lazy"
            decoding="async"
        >
    @else
        <img
            src="{{ $desktop }}"
            alt="{{ $alt }}"
            width="{{ $width }}"
            height="{{ $height }}"
            @if($eager)
                loading="eager"
                fetchpriority="high"
            @else
                loading="lazy"
            @endif
            decoding="async"
        >
    @endif
</picture>
