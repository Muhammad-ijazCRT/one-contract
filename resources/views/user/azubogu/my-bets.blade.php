@extends('layouts.dashboard')

@section('title', 'My Azubogu Bets — One-Contract™')
@section('page_title', 'My Azubogu Bets')
@section('hide_page_heading', true)
@section('body_class', 'azu-mode')
@section('layout_class', 'azu-layout')

@section('content')
<div class="azu-page">
    <div class="azu-panel azu-panel-history-page">
        <div class="azu-panel-head">
            <h3>My Bet History</h3>
            <a href="{{ route('user.azubogu.index') }}" class="azu-recent">Back to Game</a>
        </div>

        <div class="azu-history-wrap">
            <table class="azu-history azu-mybets">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Round</th>
                        <th>Numbers</th>
                        <th>Bet</th>
                        <th>Result</th>
                        <th>Payout</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bets as $bet)
                        <tr>
                            <td data-label="Time">{{ $bet['time'] }}</td>
                            <td data-label="Round">#{{ $bet['round'] }}</td>
                            <td data-label="Numbers">{{ implode(', ', $bet['numbers'] ?? []) }}</td>
                            <td data-label="Bet">${{ number_format($bet['amount'], 2) }}</td>
                            <td data-label="Result">
                                @if (($bet['status'] ?? '') === 'won')
                                    <span class="azu-outcome won">Won {{ number_format($bet['multiplier'], 2) }}×</span>
                                @elseif (($bet['status'] ?? '') === 'lost')
                                    <span class="azu-outcome lost">Lost</span>
                                @else
                                    <span class="azu-outcome active">Active</span>
                                @endif
                            </td>
                            <td data-label="Payout">
                                @if (($bet['status'] ?? '') === 'active')
                                    —
                                @else
                                    ${{ number_format($bet['payout'], 2) }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="azu-empty">No personal bets yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="azu-pagination">
            {{ $bets->links() }}
        </div>
    </div>
</div>
@endsection
