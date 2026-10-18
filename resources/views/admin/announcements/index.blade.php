@extends('layouts.admin')

@section('title', 'Announcements â€” Admin')
@section('page_title', 'Send Announcements')

@section('content')
<div class="dash-page">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <div class="panel">
        <div class="panel-header"><h3>Broadcast Announcement</h3></div>
        <p class="panel-sub">Send a notification to all active users on the platform.</p>
        <form method="POST" action="{{ route('admin.announcements.store') }}" class="dash-form" id="announcement-form">
            @csrf
            <div class="dash-field">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" maxlength="120" required placeholder="e.g. Platform Maintenance Notice">
                @error('title')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <div class="dash-field">
                <label for="message">Message</label>
                <textarea id="message" name="message" rows="5" maxlength="2000" required placeholder="Write your announcement…">{{ old('message') }}</textarea>
                @error('message')<span class="field-error">{{ $message }}</span>@enderror
            </div>
            <button type="button" class="btn btn-primary" id="send-announcement-btn">Send to All Users</button>
        </form>
    </div>

    @if ($recent->isNotEmpty())
        <div class="panel">
            <div class="panel-header"><h3>Recent Announcements</h3></div>
            <div class="admin-activity-list">
                @foreach ($recent as $item)
                    <div class="admin-activity-item">
                        <div class="admin-activity-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 17H2a2 2 0 0 0 2 2v1a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-1a2 2 0 0 0-2-2z"/><path d="M12 2v13"/></svg>
                        </div>
                        <div class="admin-activity-body">
                            <strong>{{ $item->metadata['title'] ?? 'Announcement' }}</strong>
                            <p>{{ $item->metadata['message'] ?? $item->description }}</p>
                            <span>{{ $item->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<div class="admin-modal-backdrop" id="announcement-modal" hidden>
    <div class="admin-modal">
        <div class="admin-modal-head">
            <h3>Confirm Broadcast</h3>
            <button type="button" class="admin-modal-close" data-modal-cancel aria-label="Close">×</button>
        </div>
        <p class="admin-modal-copy">Send this announcement to all active users on the platform?</p>
        <div class="admin-modal-actions">
            <button type="button" class="btn btn-outline-dark" data-modal-cancel>Cancel</button>
            <button type="button" class="btn btn-primary" data-modal-confirm>Send Now</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(() => {
    const form = document.getElementById('announcement-form');
    const openBtn = document.getElementById('send-announcement-btn');
    const modal = document.getElementById('announcement-modal');
    const confirmBtn = modal?.querySelector('[data-modal-confirm]');
    const cancelBtns = modal?.querySelectorAll('[data-modal-cancel]') || [];

    if (!form || !openBtn || !modal || !confirmBtn) return;

    const openModal = () => {
        modal.hidden = false;
        document.body.classList.add('modal-open');
    };

    const closeModal = () => {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
    };

    openBtn.addEventListener('click', openModal);
    confirmBtn.addEventListener('click', () => {
        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Sending…';
        form.submit();
    });
    cancelBtns.forEach((btn) => btn.addEventListener('click', closeModal));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeModal();
    });
})();
</script>
@endpush
@endsection
