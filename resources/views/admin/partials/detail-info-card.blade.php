<div class="admin-detail-info-card {{ ! empty($wide) ? 'admin-detail-info-card--wide' : '' }}">
    <div class="admin-detail-info-icon admin-detail-info-icon--{{ $icon ?? 'default' }}">
        @include('admin.partials.detail-icon', ['icon' => $icon ?? 'default'])
    </div>
    <div>
        <span>{{ $label }}</span>
        @if (! empty($href))
            <strong><a href="{{ $href }}">{{ $value }}</a></strong>
        @else
            <strong>{{ $value }}</strong>
        @endif
        @if (! empty($sub))
            <small>{{ $sub }}</small>
        @endif
    </div>
</div>
