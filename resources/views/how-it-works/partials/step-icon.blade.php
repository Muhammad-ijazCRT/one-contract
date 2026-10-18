@php
    $stepIcons = [
        'user' => '<i class="fa-solid fa-user fa-fw" aria-hidden="true"></i>',
        'wallet' => '<i class="fa-solid fa-wallet fa-fw" aria-hidden="true"></i>',
        'chart' => '<i class="fa-solid fa-chart-line fa-fw" aria-hidden="true"></i>',
        'monitor' => '<i class="fa-solid fa-desktop fa-fw" aria-hidden="true"></i>',
        'users' => '<i class="fa-solid fa-users fa-fw" aria-hidden="true"></i>',
        'withdraw' => '<i class="fa-solid fa-download fa-fw" aria-hidden="true"></i>',
        'building' => '<i class="fa-solid fa-building-columns fa-fw" aria-hidden="true"></i>',
        'star' => '<i class="fa-solid fa-star fa-fw" aria-hidden="true"></i>',
        'info' => '<i class="fa-solid fa-circle-info fa-fw" aria-hidden="true"></i>',
        'globe' => '<i class="fa-solid fa-globe fa-fw" aria-hidden="true"></i>',
        'warn' => '<i class="fa-solid fa-triangle-exclamation fa-fw" aria-hidden="true"></i>',
        'shield' => '<i class="fa-solid fa-shield-halved fa-fw" aria-hidden="true"></i>',
        'shield-check' => '<i class="fa-solid fa-lock fa-fw" aria-hidden="true"></i>',
        'percent' => '<i class="fa-solid fa-percent fa-fw" aria-hidden="true"></i>',
        'infinity' => '<i class="fa-solid fa-infinity fa-fw" aria-hidden="true"></i>',
        'calendar' => '<i class="fa-solid fa-calendar-days fa-fw" aria-hidden="true"></i>',
        'bolt' => '<i class="fa-solid fa-bolt fa-fw" aria-hidden="true"></i>',
        'clock' => '<i class="fa-solid fa-clock fa-fw" aria-hidden="true"></i>',
    ];
@endphp
{!! $stepIcons[$name] ?? '' !!}
