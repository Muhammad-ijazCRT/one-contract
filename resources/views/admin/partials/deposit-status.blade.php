@php
    $map = [
        'pending' => ['class' => 'pending', 'label' => 'Pending'],
        'active' => ['class' => 'active', 'label' => 'Active'],
        'rejected' => ['class' => 'rejected', 'label' => 'Rejected'],
        'withdrawn' => ['class' => 'withdrawn', 'label' => 'Withdrawn'],
    ];
    $item = $map[$status] ?? ['class' => 'pending', 'label' => ucfirst($status)];
@endphp
<span class="badge-status badge-status--{{ $item['class'] }}">{{ $item['label'] }}</span>
