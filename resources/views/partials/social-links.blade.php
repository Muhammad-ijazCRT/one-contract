@php
    $settings = $settings ?? ($contactSettings ?? null);
    $linkClass = $linkClass ?? 'social-btn';
    $wrapClass = $wrapClass ?? 'social-links';

    $socialLinks = [
        [
            'label' => 'Telegram',
            'url' => $settings?->social_telegram ?: 'https://t.me/',
            'icon' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>',
        ],
        [
            'label' => 'Twitter',
            'url' => $settings?->social_twitter ?: 'https://x.com/',
            'icon' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.227-8.451L1.5 2.25h7.06l4.259 5.699L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>',
        ],
        [
            'label' => 'Facebook',
            'url' => $settings?->social_facebook ?: 'https://facebook.com/',
            'icon' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 12.07C22 6.48 17.52 2 11.93 2S2 6.48 2 12.07c0 5.02 3.66 9.18 8.44 9.93v-7.02H8.08v-2.91h2.36V9.84c0-2.33 1.39-3.62 3.52-3.62 1.02 0 2.09.18 2.09.18v2.3h-1.18c-1.16 0-1.52.72-1.52 1.46v1.75h2.59l-.41 2.91h-2.18V22c4.78-.75 8.44-4.91 8.44-9.93z"/></svg>',
        ],
        [
            'label' => 'YouTube',
            'url' => $settings?->social_youtube ?: 'https://youtube.com/',
            'icon' => '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.5 6.2a3.02 3.02 0 0 0-2.12-2.14C19.54 3.5 12 3.5 12 3.5s-7.54 0-9.38.56A3.02 3.02 0 0 0 .5 6.2 31.6 31.6 0 0 0 0 12a31.6 31.6 0 0 0 .5 5.8 3.02 3.02 0 0 0 2.12 2.14c1.84.56 9.38.56 9.38.56s7.54 0 9.38-.56a3.02 3.02 0 0 0 2.12-2.14A31.6 31.6 0 0 0 24 12a31.6 31.6 0 0 0-.5-5.8zM9.75 15.52V8.48L15.82 12l-6.07 3.52z"/></svg>',
        ],
    ];
@endphp

<div class="{{ $wrapClass }}">
    @foreach ($socialLinks as $link)
        <a
            href="{{ $link['url'] }}"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="{{ $link['label'] }}"
            class="{{ $linkClass }}"
        >
            {!! $link['icon'] !!}
        </a>
    @endforeach
</div>
