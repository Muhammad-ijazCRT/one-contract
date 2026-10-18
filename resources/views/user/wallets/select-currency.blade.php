@extends('layouts.dashboard')

@section('title', 'Select Currency — One-Contract™')
@section('page_title', 'Select Currency')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--select-currency')
@section('layout_class', 'select-currency-layout')

@section('content')
<div class="dash-page oc-select-currency">
    @include('user.partials.alerts')

    <header class="ocscur-head">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('user.dashboard') }}" class="ocscur-back" aria-label="Back">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <h1 class="ocscur-title">SELECT <em>CURRENCY</em></h1>
        <span class="ocscur-brand" aria-hidden="true"><strong>ONE</strong> <span>CONTRACT™</span></span>
    </header>

    <section class="ocscur-panel">
        <div class="ocscur-panel-head">
            <div>
                <h2>CHOOSE CURRENCY</h2>
                <p>Select the currency you want to use on One Contract.</p>
            </div>
            <span class="ocscur-brand ocscur-brand--inline" aria-hidden="true"><strong>ONE</strong> <span>CONTRACT™</span></span>
        </div>

        <ul class="ocscur-list">
            @foreach ($currencies as $item)
                <li>
                    <form method="POST" action="{{ route('user.wallets.select-currency.store') }}" class="ocscur-form">
                        @csrf
                        <input type="hidden" name="currency" value="{{ $item['code'] }}">
                        <button
                            type="submit"
                            class="ocscur-row {{ $item['selected'] ? 'is-selected' : '' }}"
                            style="--cur-accent: {{ $item['accent'] }}"
                        >
                            <span class="ocscur-icon" aria-hidden="true">
                                <img src="{{ $item['icon'] }}" alt="">
                            </span>
                            <span class="ocscur-meta">
                                <strong>{{ $item['code'] }}</strong>
                                <small>{{ $item['label'] }}</small>
                                @if ($item['recommended'])
                                    <em class="ocscur-tag">RECOMMENDED</em>
                                @endif
                            </span>
                            <span class="ocscur-bal">
                                <span>Balance</span>
                                <b>{{ $item['formatted'] }}</b>
                            </span>
                            <i class="fa-solid fa-chevron-right ocscur-chevron" aria-hidden="true"></i>
                        </button>
                    </form>
                </li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
