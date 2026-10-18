@php
    $icons = [
        'chart' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="13" width="3.5" height="7" rx="0.5" fill="currentColor"/><rect x="9" y="9" width="3.5" height="11" rx="0.5" fill="currentColor"/><rect x="14.5" y="5" width="3.5" height="15" rx="0.5" fill="currentColor"/><circle cx="19" cy="6.5" r="3.5" fill="currentColor"/><text x="19" y="8.2" text-anchor="middle" fill="#2563eb" font-size="4.5" font-weight="800">$</text></svg>',

        'percent' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="7.5" cy="7.5" r="3" stroke="currentColor" stroke-width="2"/><circle cx="16.5" cy="16.5" r="3" stroke="currentColor" stroke-width="2"/><path d="M19 5L5 19" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>',

        'infinity' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18.2 8.2c4.8 0 4.8 7.6 0 7.6-4.2 0-6.8-7.6-11.6-7.6C2.8 8.2 2.8 15.8 7.6 15.8c4.8 0 6.4-7.6 10.6-7.6z" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>',

        'usdt' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13.5 13.5V20h-3v-6.5c-3.5-.2-6-1-6-2.25 0-1.25 2.5-2 6-2.25V6H4V3h16v3h-6.5v3c3.5.2 6 1 6 2.25 0 1.25-2.5 2-6 2.25zm-1.5-1.5c3 0 5-.5 5-1.25S15 9.5 12 9.5 7 10 7 10.75 9 12 12 12z" fill="currentColor"/></svg>',

        'calendar' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M8 3v4M16 3v4M3 10h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><text x="12" y="17.5" text-anchor="middle" fill="currentColor" font-size="7.5" font-weight="800">7</text></svg>',

        'bolt' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13.2 2.5L4.5 13.5h6.8l-1.2 8.2 9.4-11.2h-6.5l.2-7.9z" fill="currentColor"/></svg>',

        'clock' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4V1L7 5l5 4V6c3.3 0 6 2.7 6 6s-2.7 6-6 6-6-2.7-6-6H4c0 4.4 3.6 8 8 8s8-3.6 8-8-3.6-8-8-8z" fill="currentColor"/><text x="12" y="15.5" text-anchor="middle" fill="currentColor" font-size="8.5" font-weight="800">72</text></svg>',

        'users' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3" fill="currentColor"/><path d="M3.5 19.5v-1.2c0-2.4 2.2-4.3 5.5-4.3s5.5 1.9 5.5 4.3v1.2" fill="currentColor"/><circle cx="17" cy="9" r="2.5" fill="currentColor" opacity="0.85"/><path d="M14.5 19.5v-.8c0-1.8 1.6-3.2 4-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="19.5" cy="10" r="2" fill="currentColor" opacity="0.7"/><path d="M17.5 19.5v-.6c0-1.4 1.2-2.5 3-2.8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" opacity="0.85"/></svg>',

        'monitor-chart' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="4" width="20" height="13" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 14l3.5-3.5 3 2.5 3.5-4.5 4 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 20h8M12 17v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',

        'monitor' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="2" y="4" width="20" height="13" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 14l4-4 3 2.5 5-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M8 20h8M12 17v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',

        'shield-check' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5L4.5 5.5v6.2c0 5.2 3.2 9.6 7.5 11.3 4.3-1.7 7.5-6.1 7.5-11.3V5.5L12 2.5z" fill="currentColor" opacity="0.25"/><path d="M12 2.5L4.5 5.5v6.2c0 5.2 3.2 9.6 7.5 11.3 4.3-1.7 7.5-6.1 7.5-11.3V5.5L12 2.5z" stroke="currentColor" stroke-width="1.6"/><path d="M9 12.2l2 2 4.2-4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',

        'document' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-6-6z" fill="currentColor" opacity="0.2"/><path d="M14 2v6h6M8 13h8M8 17h8M8 9.5h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',

        'document-clock' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M13 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h7" stroke="currentColor" stroke-width="1.7"/><path d="M13 2v6h5M8 13h5M8 17h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="17.5" cy="16.5" r="4.5" stroke="currentColor" stroke-width="1.6"/><path d="M17.5 14.5v2.2l1.4 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',

        'admin-user' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3" stroke="currentColor" stroke-width="1.7"/><path d="M4 18.5v-1c0-2.5 2.2-4.5 5-4.5s5 2 5 4.5v1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="17.5" cy="9" r="2.2" stroke="currentColor" stroke-width="1.5"/><path d="M14.5 18.5v-.6c0-1.5 1.3-2.7 3.2-3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><circle cx="17.5" cy="12" r="2.8" stroke="currentColor" stroke-width="1.4" opacity="0.55"/><path d="M12 3.5l.8.7 1 .15.45.9.9.45-.15 1 .7.8-.7.8.15 1-.9.45-.45.9-1 .15-.8.7-.8-.7-1-.15.9-.45.45-.9 1-.15.8-.7z" stroke="currentColor" stroke-width="1.2"/></svg>',

        'bell' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 8.2A6 6 0 0 0 6 8.2c0 6.8-2.8 8.8-2.8 8.8h17.6S18 15 18 8.2z" fill="currentColor" opacity="0.25"/><path d="M18 8.2A6 6 0 0 0 6 8.2c0 6.8-2.8 8.8-2.8 8.8h17.6S18 15 18 8.2z" stroke="currentColor" stroke-width="1.8"/><path d="M13.7 20.5a1.8 1.8 0 0 1-3.4 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',

        'lock-shield' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5L4.5 5.5v6.2c0 5.2 3.2 9.6 7.5 11.3 4.3-1.7 7.5-6.1 7.5-11.3V5.5L12 2.5z" fill="currentColor" opacity="0.25"/><path d="M12 2.5L4.5 5.5v6.2c0 5.2 3.2 9.6 7.5 11.3 4.3-1.7 7.5-6.1 7.5-11.3V5.5L12 2.5z" stroke="currentColor" stroke-width="1.6"/><rect x="9.5" y="10.5" width="5" height="4.5" rx="1" stroke="currentColor" stroke-width="1.6"/><path d="M10.5 10.5V9a1.5 1.5 0 0 1 3 0v1.5" stroke="currentColor" stroke-width="1.6"/></svg>',

        'devices' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="1.5" y="5" width="13" height="9" rx="1.2" stroke="currentColor" stroke-width="1.5"/><path d="M5 17h6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><rect x="15" y="7" width="5" height="8" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="17.5" y="9.5" width="2.5" height="4" rx="0.4" fill="currentColor" opacity="0.35"/><rect x="10" y="14" width="6" height="4.5" rx="0.8" stroke="currentColor" stroke-width="1.4"/></svg>',

        'gear' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.8v2.2M12 19v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.8 12h2.2M19 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M12 5.5l1 .9 1.3-.2.6 1.2 1.2.6-.2 1.3.9 1-.9 1 .2 1.3-1.2.6-.6 1.2-1.3-.2-1 .9-1-1-.9-.2-1.3 1.2-.6.6-1.2 1.3.2 1-.9z" fill="currentColor" opacity="0.35"/></svg>',

        'admin' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.8"/><path d="M12 2.8v2.2M12 19v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.8 12h2.2M19 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',

        'eye' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s4.2-7 10-7 10 7 10 7-4.2 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="12" r="3" fill="currentColor"/></svg>',

        'globe' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><ellipse cx="12" cy="12" rx="4" ry="9" stroke="currentColor" stroke-width="1.5"/><path d="M3 12h18M5.2 7h13.6M5.2 17h13.6" stroke="currentColor" stroke-width="1.5"/></svg>',

        'rocket' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 14.5l-2.8-2.8a18 18 0 0 1 2.2-4.2A11 11 0 0 1 21 3c0 2.2-.6 5.8-4.5 8.8a17 17 0 0 1-4.5 2.7z" fill="currentColor" opacity="0.3"/><path d="M12 14.5l-2.8-2.8a18 18 0 0 1 2.2-4.2A11 11 0 0 1 21 3c0 2.2-.6 5.8-4.5 8.8a17 17 0 0 1-4.5 2.7z" stroke="currentColor" stroke-width="1.6"/><path d="M5 19.5c-1.2 1-1.8 3.8-1.8 3.8s2.9-.5 3.9-1.7c.6-.7.6-1.7-.1-2.4-.7-.7-1.7-.7-2.4 0z" fill="currentColor"/><path d="M15 8l1.5 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',

        'building' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 21h18" stroke="currentColor" stroke-width="1.8"/><path d="M5 21V8l7-4v17" stroke="currentColor" stroke-width="1.8"/><path d="M12 21V12l7-4v13" stroke="currentColor" stroke-width="1.8"/><path d="M7.5 11h2M7.5 14.5h2M7.5 18h2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',

        'shield' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2.5L4.5 5.5v6.2c0 5.2 3.2 9.6 7.5 11.3 4.3-1.7 7.5-6.1 7.5-11.3V5.5L12 2.5z" stroke="currentColor" stroke-width="1.8"/></svg>',
    ];
@endphp
{!! $icons[$name] ?? '' !!}
