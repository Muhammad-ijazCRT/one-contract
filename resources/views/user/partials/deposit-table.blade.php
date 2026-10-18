<div class="table-wrap">
    <table class="dash-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Amount</th>
                <th>Wallet</th>
                <th>Status</th>
                <th>Network</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($deposits as $row)
                @php
                    $wallet = $row['wallet'] ?? null;
                @endphp
                <tr>
                    <td data-label="ID"><span class="cell-sub">{{ $row['id_label'] }}</span></td>
                    <td data-label="Amount"><strong>{{ $row['amount_label'] }}</strong></td>
                    <td data-label="Wallet">
                        @if ($wallet)
                            <span class="ocdep-wallet-chip">
                                <img src="{{ $wallet->iconUrl() }}" alt="" width="18" height="18">
                                {{ $wallet->shortLabel() }}
                            </span>
                        @else
                            <span class="cell-sub">—</span>
                        @endif
                    </td>
                    <td data-label="Status">@include('user.partials.deposit-status-badge', ['status' => $row['status']])</td>
                    <td data-label="Network">{{ $row['network'] }}</td>
                    <td data-label="Date">{{ $row['date']?->format('M d, Y h:i A') ?? '—' }}</td>
                    <td data-label="Action" class="table-actions">
                        <a href="{{ $row['url'] }}" class="action-btn action-btn--view action-btn--pill" @if (str_starts_with((string) $row['url'], 'http')) target="_blank" rel="noopener" @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-cell">{{ $emptyMessage ?? 'No deposits yet.' }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
