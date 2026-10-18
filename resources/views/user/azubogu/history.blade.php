@extends('layouts.dashboard')

@section('title', 'Azubogu Round History — One-Contract™')
@section('page_title', 'Azubogu History')
@section('hide_page_heading', true)
@section('body_class', 'azu-mode')
@section('layout_class', 'azu-layout')

@section('content')
<div class="azu-page">
    <div class="azu-panel azu-panel-history-page">
        <div class="azu-panel-head">
            <h3>Azubogu Round History</h3>
            <a href="{{ route('user.azubogu.index') }}" class="azu-recent">Back to Game</a>
        </div>

        <div class="azu-history-wrap">
            <table class="azu-history">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Round</th>
                        <th>0×</th>
                        <th>0.45×</th>
                        <th>1.40×</th>
                        <th>1.90×</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($history as $h)
                        <tr>
                            <td>{{ $h['time'] }}</td>
                            <td>#{{ $h['round_number'] }}</td>
                            <td class="c-0">{{ implode(', ', $h['groups']['0x'] ?? []) }}</td>
                            <td class="c-045">{{ implode(', ', $h['groups']['0.45x'] ?? []) }}</td>
                            <td class="c-140">{{ implode(', ', $h['groups']['1.40x'] ?? []) }}</td>
                            <td class="c-190">{{ implode(', ', $h['groups']['1.90x'] ?? []) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="azu-empty">No completed rounds yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="azu-pagination">
            {{ $history->links() }}
        </div>
    </div>
</div>
@endsection
