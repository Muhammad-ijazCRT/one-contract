@extends('layouts.dashboard')

@section('title', 'Pending Deposits — One-Contract™')
@section('page_title', 'Pending Deposits')
@section('hide_page_heading', true)

@section('content')
<div class="dash-page">
    <!-- <div class="dash-alert dash-alert-info">
        Deposits shown here are awaiting coin selection or blockchain confirmation. They'll move to your deposit history automatically once confirmed.
    </div> -->

    <div class="panel">
        <div class="panel-header">
            <h3>Pending Deposits <span class="badge-status pending">{{ $deposits->total() }}</span></h3>
            <a href="{{ route('user.deposit.index') }}" class="action-btn action-btn--view action-btn--pill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                New Deposit
            </a>
        </div>
        @include('user.partials.deposit-table', [
            'deposits' => $deposits,
            'emptyMessage' => 'No pending deposits.',
        ])
        {{ $deposits->links() }}
    </div>
</div>
@endsection
