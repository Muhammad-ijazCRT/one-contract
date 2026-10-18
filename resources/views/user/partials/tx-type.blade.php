@php
    $typeValue = $type instanceof \App\Enums\TransactionType ? $type->value : (string) $type;
    $label = $label ?? str_replace('_', ' ', ucwords($typeValue, '_'));
@endphp
<span class="tx-type-wrap">
    <span class="tx-icon tx-icon-{{ $typeValue }}" aria-hidden="true">
        @switch($typeValue)
            @case('deposit')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M12 12v6M9 15l3 3 3-3"/></svg>
                @break
            @case('daily_reward')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 16l4-8 4 4 6-10"/></svg>
                @break
            @case('referral_commission')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                @break
            @case('reward_withdrawal')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                @break
            @case('capital_withdrawal')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7"/><path d="M16 6l-4-4-4 4"/><path d="M12 2v14"/></svg>
                @break
            @case('admin_action')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                @break
            @default
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
        @endswitch
    </span>
    <span class="tx-type-label">{{ $label }}</span>
</span>
