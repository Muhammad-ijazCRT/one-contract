<div class="settings-tab-intro">
    <div>
        <h3>Support Tickets</h3>
        <p class="panel-sub">Review and respond to user support requests.</p>
    </div>
</div>
<div class="table-wrap">
    <table class="dash-table">
        <thead>
            <tr>
                <th>Requester</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Submitted</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($tickets ?? []) as $ticket)
                <tr>
                    <td>{{ $ticket->name }}<br><small>{{ $ticket->email }}</small></td>
                    <td>{{ $ticket->subject_label }}</td>
                    <td><span class="status-badge {{ $ticket->status === 'open' ? 'success' : 'secondary' }}">{{ ucfirst($ticket->status) }}</span></td>
                    <td>{{ $ticket->created_at->format('M d, Y H:i') }}</td>
                    <td><a href="{{ route('admin.support.show', $ticket) }}" class="btn btn-sm btn-outline">View</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-cell">No support tickets found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ ($tickets ?? collect())->links() }}
