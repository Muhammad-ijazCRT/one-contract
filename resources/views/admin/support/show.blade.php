@extends('layouts.admin')

@section('title', 'Support Ticket — Admin')
@section('page_title', 'Support Ticket')

@section('content')
<div class="dash-page admin-detail-page admin-detail-page--purple">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <div class="panel admin-detail-panel">
        @include('admin.partials.detail-hero', [
            'icon' => 'support',
            'label' => 'Support Ticket',
            'title' => 'Ticket #'.$ticket->id,
            'backUrl' => route('admin.support.index'),
            'backLabel' => 'Back to Tickets',
            'badges' => [
                ['text' => ucfirst($ticket->status), 'class' => 'status-badge '.($ticket->status === 'open' ? 'success' : 'secondary')],
                ['text' => $ticket->user_id ? 'User Dashboard' : 'Contact Page', 'class' => 'admin-detail-pill '.($ticket->user_id ? 'admin-detail-pill--info' : '')],
            ],
        ])

        <div class="admin-detail-info-grid">
            @include('admin.partials.detail-info-card', ['icon' => 'user', 'label' => 'Requester', 'value' => $ticket->name])
            @include('admin.partials.detail-info-card', ['icon' => 'email', 'label' => 'Email', 'value' => $ticket->email, 'href' => 'mailto:'.$ticket->email])
            @include('admin.partials.detail-info-card', ['icon' => 'subject', 'label' => 'Subject', 'value' => $ticket->subject_label])
            @include('admin.partials.detail-info-card', ['icon' => 'time', 'label' => 'Submitted', 'value' => $ticket->created_at->format('M d, Y'), 'sub' => $ticket->created_at->format('h:i A')])
        </div>

        @include('admin.partials.detail-note', [
            'title' => 'Message',
            'meta' => $ticket->created_at->diffForHumans(),
            'body' => $ticket->message,
        ])

        <form method="POST" action="{{ route('admin.support.update', $ticket) }}" class="admin-detail-footer">
            @csrf
            @method('PUT')
            <div class="admin-detail-footer-copy">
                <h4>Update Ticket</h4>
                <p>Change the status once you have replied to the customer.</p>
            </div>
            <div class="admin-detail-footer-controls">
                <div class="dash-field admin-detail-field">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="panel-select" required>
                        <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                        <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-gold">Save Status</button>
            </div>
        </form>
    </div>
</div>
@endsection
