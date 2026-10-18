@php
    $badgeClass = match ($status) {
        'completed', 'active', 'approved' => 'active',
        'pending' => 'pending',
        'rejected', 'failed' => 'badge-status--rejected',
        'withdrawn' => 'badge-status--withdrawn',
        default => 'pending',
    };
    $label = in_array($status, ['active'], true) ? 'Active' : ucfirst($status);
@endphp
<span class="badge-status {{ $badgeClass }}">{{ $label }}</span>
