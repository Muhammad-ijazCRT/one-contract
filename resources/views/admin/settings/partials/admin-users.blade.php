<div class="settings-tab-intro">
    <div>
        <h3>Admin Users</h3>
        <p class="panel-sub">View and manage registered platform members.</p>
    </div>
</div>
<div class="table-wrap">
    <table class="dash-table">
        <thead>
            <tr>
                <th>Member ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($users ?? []) as $user)
                <tr>
                    <td><strong>{{ $user->member_id }}</strong></td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td><span class="status-badge {{ $user->status->value === 'active' ? 'success' : 'danger' }}">{{ ucfirst($user->status->value) }}</span></td>
                    <td>{{ $user->created_at->format('M d, Y') }}</td>
                    <td data-label="Actions" class="table-actions">
                        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-outline-dark btn-sm action-btn action-btn--view">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                            View
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-cell">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ ($users ?? collect())->links() }}
