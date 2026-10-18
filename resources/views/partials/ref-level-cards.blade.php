@php
    $inline = $inline ?? false;
@endphp
@if (! $inline)
    <div class="ref-stats-row">
@endif

@foreach ($levels as $level => $data)
    @php
        $levelColors = [1 => 'gold', 2 => 'blue', 3 => 'purple'];
        $color = $levelColors[$level] ?? 'blue';
    @endphp

    <article class="ref-stat-card ref-stat-card--{{ $color }}">
        <div class="ref-stat-head">
            <div class="ref-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="9" cy="8" r="3" fill="currentColor"/>
                    <path d="M3.5 19.5v-1.2c0-2.4 2.2-4.3 5.5-4.3s5.5 1.9 5.5 4.3v1.2" fill="currentColor"/>
                    @if ($level >= 2)
                        <circle cx="17" cy="9" r="2.5" fill="currentColor" opacity="0.85"/>
                    @endif
                    @if ($level >= 3)
                        <circle cx="19.5" cy="10" r="2" fill="currentColor" opacity="0.7"/>
                    @endif
                </svg>
            </div>
            <div class="ref-stat-meta">
                <span class="ref-stat-level">Level {{ $level }}</span>
                <span class="ref-stat-rate">{{ number_format($data['rate'], 0) }}% Commission</span>
            </div>
        </div>

        <div class="ref-stat-body">
            <strong class="ref-stat-count">{{ number_format($data['count']) }}</strong>
            <span class="ref-stat-label">{{ $label ?? 'Referrals' }}</span>
        </div>

        <div class="ref-stat-foot">
            <span class="ref-stat-earned">{{ number_format($data['earnings'], 2) }}</span>
            <span class="ref-stat-earned-unit">USDT earned</span>
        </div>
    </article>
@endforeach

@if (! $inline)
    </div>
@endif
