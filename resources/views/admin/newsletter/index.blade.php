@extends('layouts.admin')

@section('title', 'Newsletter Subscribers — Admin')
@section('page_title', 'Newsletter Subscribers')

@section('content')
<div class="dash-page">
    @if (session('success'))
        <div class="dash-alert dash-alert-success">{{ session('success') }}</div>
    @endif

    <div class="admin-filter-stats">
        <span class="admin-filter-pill active">
            Total: {{ number_format($totalCount) }}
        </span>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3>Newsletter Subscribers</h3>
            <form method="GET" action="{{ route('admin.newsletter.index') }}" class="admin-search-form">
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search email…"
                    aria-label="Search subscribers"
                >
                <button type="submit" class="btn btn-outline-dark btn-sm">Search</button>
                @if ($search !== '')
                    <a href="{{ route('admin.newsletter.index') }}" class="btn btn-outline-dark btn-sm">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-wrap">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Email</th>
                        <th>Subscribed</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subscribers as $subscriber)
                        <tr>
                            <td data-label="#">{{ $subscribers->firstItem() + $loop->index }}</td>
                            <td data-label="Email">{{ $subscriber->email }}</td>
                            <td data-label="Subscribed">
                                {{ ($subscriber->subscribed_at ?? $subscriber->created_at)?->format('M d, Y H:i') }}
                            </td>
                            <td data-label="Action" class="table-actions">
                                <form
                                    method="POST"
                                    action="{{ route('admin.newsletter.destroy', $subscriber) }}"
                                    onsubmit="return confirm('Remove this subscriber?');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-dark btn-sm">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty-cell">
                                @if ($search !== '')
                                    No subscribers match “{{ $search }}”.
                                @else
                                    No newsletter subscribers yet.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $subscribers->links() }}
    </div>
</div>
@endsection
