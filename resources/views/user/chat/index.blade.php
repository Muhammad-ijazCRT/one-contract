@extends('layouts.dashboard')

@section('title', 'Global Chat — One-Contract™')
@section('page_title', 'Global Chat')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--chat')

@php
    $logo = asset('images/logo.png');
    $navbarDice = asset('images/chat/chat_dice_logo.png');
    $globeIcon = asset('images/chat/chat_globe_icon.png');
@endphp

@section('content')
<div class="oc-gchat-screen-wrapper">
    <div
        class="dash-page oc-gchat oc-gchat-container"
        data-oc-gchat
        data-channel="{{ $channel }}"
        data-messages-url="{{ route('user.chat.messages') }}"
        data-send-url="{{ route('user.chat.store') }}"
        data-react-url="{{ url('/dashboard/chat/messages') }}"
        data-csrf="{{ csrf_token() }}"
        data-can-post="{{ $canPost ? '1' : '0' }}"
        data-reaction-emojis='@json(\App\Services\ChatService::REACTION_EMOJIS)'
        data-composer-emojis='@json(\App\Services\ChatService::COMPOSER_EMOJIS)'
    >
        {{-- ═══════════════════════════════════════════════════
             1. STICKY TOP HEADER (Top Nav Bar + Channel Info Bar)
             Always sticks at the top when scrolling messages
        ═══════════════════════════════════════════════════ --}}
        <header class="ocg-sticky-header">
            {{-- Top Navbar Row: Back link, Brand + 3D dice, Bell + Avatar --}}
            <div class="ocg-top-nav">
                <a href="{{ route('user.dashboard') }}" class="ocg-back-btn" aria-label="Back to Dashboard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </a>

                <div class="ocg-brand-cluster">
                    <img src="{{ $navbarDice }}" alt="ONE CONTRACT" class="ocg-brand-dice" decoding="async">
                    <div class="ocg-brand-titles">
                        <span class="ocg-brand-title">ONE <em>CONTRACT</em><sup>&trade;</sup></span>
                        <span class="ocg-brand-subtitle">WORLD SMARTEST IGAMING PLATFORM</span>
                    </div>
                </div>

                <div class="ocg-nav-actions">
                    <a href="{{ route('user.notifications.index') }}" class="ocg-nav-icon-circle ocg-nav-bell" aria-label="Notifications">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span class="ocg-bell-dot" aria-hidden="true"></span>
                    </a>
                    <a href="{{ route('user.profile.edit') }}" class="ocg-nav-icon-circle" aria-label="Profile">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Channel Info Pill / Bar --}}
            <div class="ocg-channel-card">
                <div class="ocg-channel-icon-wrap">
                    <img src="{{ $globeIcon }}" alt="Global Chat" class="ocg-channel-globe-img">
                </div>
                <div class="ocg-channel-info">
                    <div class="ocg-channel-title-row">
                        <h1 class="ocg-channel-name">{{ $channels[$channel]['name'] ?? 'Global Chat' }}</h1>
                        <span class="ocg-online-badge">
                            <span class="ocg-online-dot"></span>
                            <span data-ocg-online>{{ number_format($onlineCount) }}</span> Online
                        </span>
                    </div>
                    <p class="ocg-channel-desc">
                        Chat with players from around the world. Be respectful and follow the community rules.
                    </p>
                </div>
                <div class="ocg-channel-menu">
                    <button type="button" class="ocg-menu-dots-btn" aria-label="Channel Options" title="Options">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <circle cx="12" cy="5" r="2"></circle>
                            <circle cx="12" cy="12" r="2"></circle>
                            <circle cx="12" cy="19" r="2"></circle>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        {{-- Hidden optional legacy pin alert if collapsed --}}
        <div class="ocg-pin {{ $pinned ? '' : 'is-empty' }}" data-ocg-pin {{ $pinned ? '' : 'hidden' }} style="display:none !important;">
            <div class="ocg-pin-top">
                <strong><i class="fa-solid fa-thumbtack"></i> Pinned Message</strong>
            </div>
            <p data-ocg-pin-body>{{ $pinned['body'] ?? '' }}</p>
        </div>

        {{-- ═══════════════════════════════════════════════════
             2. CHAT STREAM (Scrollable Message Feed)
        ═══════════════════════════════════════════════════ --}}
        <div class="ocg-stream" data-ocg-stream>
            @php $lastDay = null; @endphp
            @forelse ($messages as $msg)
                @if (($msg['day'] ?? null) !== $lastDay)
                    @php $lastDay = $msg['day']; @endphp
                    <div class="ocg-day" data-ocg-day="{{ $lastDay }}" style="display:none;">{{ $lastDay }}</div>
                @endif
                @include('user.chat.partials.message', ['msg' => $msg])
            @empty
                <p class="ocg-empty" data-ocg-empty>No messages yet in {{ $channels[$channel]['name'] ?? 'this channel' }}. Be the first to say hello.</p>
            @endforelse
        </div>

        {{-- ═══════════════════════════════════════════════════
             3. COMPOSER BAR (Sticky Bottom Pill Input)
        ═══════════════════════════════════════════════════ --}}
        <div class="ocg-composer-shell">
            <div class="ocg-emoji-panel" data-ocg-emoji-panel hidden>
                <div class="ocg-emoji-panel-grid" data-ocg-emoji-grid></div>
            </div>
            <form class="ocg-composer {{ $canPost ? '' : 'is-readonly' }}" data-ocg-form>
                <div class="ocg-composer-pill">
                    <button type="button" class="ocg-composer-btn ocg-composer-emoji-toggle" data-ocg-composer-emoji aria-label="Select emoji" {{ $canPost ? '' : 'disabled' }}>
                        <i class="fa-regular fa-face-smile"></i>
                    </button>
                    <input
                        type="text"
                        name="body"
                        maxlength="500"
                        placeholder="{{ $canPost ? 'Type a message...' : 'Read-only channel' }}"
                        {{ $canPost ? 'required' : 'disabled' }}
                        autocomplete="off"
                        data-ocg-input
                        class="ocg-composer-input"
                    >
                    <button type="button" class="ocg-composer-btn ocg-composer-attach" aria-label="Attach file" {{ $canPost ? '' : 'disabled' }}>
                        <i class="fa-solid fa-paperclip"></i>
                    </button>
                </div>
                <button type="submit" class="ocg-send-neon-btn" aria-label="Send message" {{ $canPost ? '' : 'disabled' }}>
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </form>
        </div>
        <p class="ocg-error" data-ocg-error hidden></p>
    </div>
</div>
@endsection

@push('styles')
@vite(['resources/css/global-chat.css'])
@endpush

@push('scripts')
@vite(['resources/js/global-chat.js'])
<script type="application/json" id="ocg-bootstrap">@json(['messages' => $messages, 'pinned' => $pinned])</script>
@endpush
