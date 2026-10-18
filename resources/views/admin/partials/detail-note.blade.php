<div class="admin-detail-note">
    <div class="admin-detail-note-header">
        <h4>{{ $title }}</h4>
        @if (! empty($meta))
            <span>{{ $meta }}</span>
        @endif
    </div>
    <div class="admin-detail-note-body">{{ $body }}</div>
</div>
