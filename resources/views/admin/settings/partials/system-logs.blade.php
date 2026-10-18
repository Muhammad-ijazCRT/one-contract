<div class="settings-tab-intro">
    <div>
        <h3>System Logs</h3>
        <p class="panel-sub">Audit trail of admin actions across the platform.</p>
    </div>
</div>
<div class="table-wrap">
    <table class="dash-table">
        <thead>
            <tr>
                <th>Admin</th>
                <th>Action</th>
                <th>Target</th>
                <th>IP</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($logs ?? []) as $log)
                <tr>
                    <td>{{ $log->admin?->name ?? 'System' }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $log->target_type ? class_basename($log->target_type) . ' (#'.$log->target_id.')' : '-' }}</td>
                    <td>{{ $log->ip_address }}</td>
                    <td>{{ $log->created_at->format('M d, Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-cell">No logs found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ ($logs ?? collect())->links() }}
