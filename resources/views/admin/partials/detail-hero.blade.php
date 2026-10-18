<div class="admin-detail-hero">
    <div class="admin-detail-hero-main">
        <div class="admin-detail-hero-icon" aria-hidden="true">
            @include('admin.partials.detail-icon', ['icon' => $icon ?? 'default'])
        </div>
        <div>
            <p class="admin-detail-label">{{ $label }}</p>
            <h3 class="admin-detail-title">{{ $title }}</h3>
            @if (! empty($badges))
                <div class="admin-detail-badges">
                    @foreach ($badges as $badge)
                        <span class="{{ $badge['class'] ?? 'admin-detail-pill' }}">{{ $badge['text'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="admin-detail-hero-actions">
        @if (! empty($actions))
            {!! $actions !!}
        @endif
        <a href="{{ $backUrl }}" class="btn btn-outline-dark btn-sm admin-detail-back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            {{ $backLabel ?? 'Back' }}
        </a>
    </div>
</div>
