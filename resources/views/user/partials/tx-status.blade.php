@php
    $statusClass = match ($status) {
        'completed', 'active' => 'active',
        'approved', 'pending' => 'pending',
        'rejected', 'failed', 'withdrawn', 'cancelled' => 'inactive',
        default => 'pending',
    };
    $label = in_array($status, ['active'], true) ? 'Active' : ucfirst($status);
@endphp
<span class="badge-status {{ $statusClass }}">{{ $label }}</span>
