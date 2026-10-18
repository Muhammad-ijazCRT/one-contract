@extends('layouts.admin')

@section('title', 'Support Tickets — Admin')
@section('page_title', 'Support Tickets')

@section('content')
<div class="dash-page">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <div class="admin-filter-stats">
        <a href="{{ route('admin.support.index') }}" class="admin-filter-pill {{ ! $statusFilter ? 'active' : '' }}">
            All
        </a>
        <a href="{{ route('admin.support.index', ['status' => 'open']) }}" class="admin-filter-pill {{ $statusFilter === 'open' ? 'active' : '' }}">
            Open
        </a>
        <a href="{{ route('admin.support.index', ['status' => 'resolved']) }}" class="admin-filter-pill {{ $statusFilter === 'resolved' ? 'active' : '' }}">
            Resolved
        </a>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>Support Tickets</h3>
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
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td data-label="Requester">{{ $ticket->name }}<br><small>{{ $ticket->email }}</small></td>
                            <td data-label="Subject">{{ $ticket->subject_label }}</td>
                            <td data-label="Status">
                                <span class="status-badge {{ $ticket->status === 'open' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($ticket->status) }}
                                </span>
                            </td>
                            <td data-label="Submitted">{{ $ticket->created_at->format('M d, Y H:i') }}</td>
                            <td data-label="Action" class="table-actions">
                                <button type="button" class="btn btn-outline-dark btn-sm action-btn action-btn--view" data-detail-modal="{{ route('admin.support.show', $ticket) }}">
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-cell">No support tickets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $tickets->links() }}
    </div>
</div>
@endsection
