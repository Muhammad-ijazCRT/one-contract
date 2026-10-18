@php
    $svgIcons = [
        'chart' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="13" width="3" height="6.5" rx="0.75" fill="currentColor"/><rect x="8.5" y="10" width="3" height="9.5" rx="0.75" fill="currentColor"/><rect x="13" y="7" width="3" height="12.5" rx="0.75" fill="currentColor"/><rect x="17.5" y="4" width="3" height="15.5" rx="0.75" fill="currentColor"/><path d="M5 17.5L9.5 12.5L13.5 15.5L18.5 10.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.5 10.5L21 10.5M18.5 10.5L18.5 7.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/></svg>',
        'shield-check' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5L4.5 5.5v6.2c0 5.2 3.2 9.6 7.5 11.3 4.3-1.7 7.5-6.1 7.5-11.3V5.5L12 2.5z" fill="currentColor" opacity="0.25"/><path d="M12 2.5L4.5 5.5v6.2c0 5.2 3.2 9.6 7.5 11.3 4.3-1.7 7.5-6.1 7.5-11.3V5.5L12 2.5z" stroke="currentColor" stroke-width="1.9"/><path d="M9 12.2l2 2 4.2-4.5" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    ];

    $faIcons = [
        'calendar' => 'fa-calendar-days',
        'users' => 'fa-user-group',
        'headset' => 'fa-headset',
        'target' => 'fa-bullseye',
        'eye' => 'fa-eye',
        'diamond' => 'fa-gem',
        'briefcase' => 'fa-briefcase',
        'building-columns' => 'fa-building-columns',
        'circle-info' => 'fa-circle-info',
    ];

    $iconSvg = $svgIcons[$name] ?? null;
    $faClass = $faIcons[$name] ?? null;
@endphp

@if ($iconSvg)
    {!! $iconSvg !!}
@elseif ($faClass)
    <i class="fa-solid fa-fw {{ $faClass }}" aria-hidden="true"></i>
@else
    @php $faName = str_replace('_', '-', $name); @endphp
    <i class="fa-solid fa-fw fa-{{ $faName }}" aria-hidden="true"></i>
@endif
