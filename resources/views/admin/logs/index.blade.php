@extends('layouts.admin')

@section('title', 'System Logs — Admin')
@section('page_title', 'System Logs')

@section('content')
<div class="dash-page">
    <div class="panel">
        <div class="panel-header"><h3>Administrative Actions</h3></div>
        <div class="table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td data-label="Admin">
                                <strong>{{ $log->admin?->name ?? '—' }}</strong>
                            </td>
                            <td data-label="Action">
                                <span class="badge-status pending">{{ ucwords(str_replace('_', ' ', $log->action)) }}</span>
                            </td>
                            <td data-label="Description">{{ $log->description ?? '—' }}</td>
                            <td data-label="IP"><code>{{ $log->ip_address ?? '—' }}</code></td>
                            <td data-label="Date">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-cell">No admin actions logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </div>
</div>
@endsection
