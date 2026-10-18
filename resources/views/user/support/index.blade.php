@extends('layouts.dashboard')

@section('title', 'Support Center — One-Contract™')
@section('page_title', 'Support Center')
@section('hide_page_heading', true)
@section('body_class', 'dash-body--home dash-body--support')
@section('layout_class', 'support-layout')

@php
    $logo = asset('images/logo.png');
    $hero = asset('images/support/hero-cube.png');
    $heroFallback = asset('images/support/hero-cube.svg');
    $supportEmail = $contactSettings?->email ?: 'support@one-contract.com';
    $faqs = [
        [
            'icon' => 'fa-circle-question',
            'tone' => 'gold',
            'title' => 'What is One Contract?',
            'desc' => 'Learn more about One Contract platform and how it works.',
            'answer' => 'One-Contract™ is a smart USDT platform for investment schemes, lending, games, and rewards — all in one place.',
            'url' => route('about'),
        ],
        [
            'icon' => 'fa-wallet',
            'tone' => 'green',
            'title' => 'How do I deposit?',
            'desc' => 'Step-by-step guide on how to deposit funds into your account.',
            'answer' => 'Open Deposit from your dashboard, choose a supported crypto, send funds, and wait for confirmation.',
            'url' => route('user.deposit.index'),
        ],
        [
            'icon' => 'fa-coins',
            'tone' => 'gold',
            'title' => 'How do I withdraw?',
            'desc' => 'Find out how to withdraw your funds and processing time.',
            'answer' => 'Go to Withdraw, enter your USDT amount and wallet address, complete verification, then wait for processing.',
            'url' => route('user.withdraw.index'),
        ],
        [
            'icon' => 'fa-shield-halved',
            'tone' => 'green',
            'title' => 'Is One Contract secure?',
            'desc' => 'Yes, your security is our top priority. Learn more about it.',
            'answer' => 'We use verified accounts, secure wallets, and clear platform rules to protect your capital and identity.',
            'url' => route('faq'),
        ],
        [
            'icon' => 'fa-user',
            'tone' => 'gold',
            'title' => 'KYC Verification',
            'desc' => 'Information about identity verification and account security.',
            'answer' => 'Withdrawals unlock after an admin confirms your identity. Crypto payouts are confirmed with the email code on the withdraw page.',
            'url' => route('user.profile.edit'),
        ],
    ];
@endphp

@push('styles')
@vite(['resources/css/support-mock.css'])
@endpush

@section('content')
<div class="ocsc-page" id="support-overview">
    @if (session('success'))
        <div class="ocsc-alert">{{ session('success') }}</div>
    @endif

    {{-- Hero --}}
    <header class="ocsc-hero">
        <div class="ocsc-hero-copy">
            <div class="ocsc-brand">
                <img src="{{ $logo }}" alt="ONE CONTRACT™" class="oc-brand-logo" decoding="async">
                <span>ONE <em>CONTRACT</em><sup>™</sup></span>
            </div>
            <h1>SUPPORT <span>CENTER</span></h1>
            <p>We’re here to help you 24/7</p>
        </div>
        <div class="ocsc-hero-art" aria-hidden="true">
            <img src="{{ $hero }}?v=3" alt="" class="ocsc-cube"
                 onerror="this.onerror=null;this.src='{{ $heroFallback }}';">
        </div>
    </header>

    {{-- Need help CTA --}}
    <section class="ocsc-help-card">
        <div class="ocsc-help-copy">
            <strong>NEED HELP?</strong>
            <p>Get fast and reliable support for any issues or questions you may have.</p>
        </div>
        <a href="#support-submit" class="ocsc-btn-solid">
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
            CONTACT SUPPORT
        </a>
    </section>

    {{-- About / FAQ --}}
    <section class="ocsc-card ocsc-faq-card">
        <div class="ocsc-card-head">
            <h2><span>ABOUT ONE CONTRACT</span></h2>
            <a href="{{ route('faq') }}" class="ocsc-link">VIEW ALL <i class="fa-solid fa-chevron-right"></i></a>
        </div>
        <ul class="ocsc-faq-list">
            @foreach ($faqs as $i => $faq)
                <li class="ocsc-faq-item" data-ocsc-faq>
                    <button type="button" class="ocsc-faq-btn" aria-expanded="false">
                        <span class="ocsc-faq-ico ocsc-faq-ico--{{ $faq['tone'] }}"><i class="fa-solid {{ $faq['icon'] }}"></i></span>
                        <span class="ocsc-faq-text">
                            <strong>{{ $faq['title'] }}</strong>
                            <em>{{ $faq['desc'] }}</em>
                        </span>
                        <i class="fa-solid fa-chevron-right ocsc-faq-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="ocsc-faq-panel" hidden>
                        <p>{{ $faq['answer'] }}</p>
                        <a href="{{ $faq['url'] }}">Open guide <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Contact options --}}
    <section class="ocsc-card ocsc-contact-card">
        <h2 class="ocsc-section-title">CONTACT OPTIONS</h2>
        <div class="ocsc-contact-row">
            <span class="ocsc-contact-ico"><i class="fa-solid fa-paper-plane"></i></span>
            <div>
                <strong>CONTACT SUPPORT</strong>
                <em>Our support team is ready to assist you.</em>
            </div>
        </div>
        <a href="mailto:{{ $supportEmail }}" class="ocsc-btn-outline">REPLY AS SOON AS POSSIBLE</a>
    </section>

    {{-- Submit request --}}
    <section class="ocsc-card ocsc-submit-card" id="support-submit">
        <h2 class="ocsc-section-title">SUBMIT A REQUEST</h2>
        <p class="ocsc-submit-lead">Can’t find what you need? Send us a message and we’ll get back to you.</p>

        <button type="button" class="ocsc-btn-outline ocsc-btn-toggle" id="ocsc-toggle-form" aria-expanded="false" aria-controls="ocsc-form-wrap">
            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
            SUBMIT REQUEST
        </button>

        <div class="ocsc-form-wrap" id="ocsc-form-wrap" hidden>
            <form method="POST" action="{{ route('user.support.store') }}" class="ocsc-form">
                @csrf
                <label class="ocsc-field">
                    <span>Subject</span>
                    <select name="subject" required>
                        <option value="">Choose a topic</option>
                        @foreach (App\Models\SupportTicket::subjectLabels() as $key => $label)
                            <option value="{{ $key }}" {{ old('subject') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('subject')<em class="ocsc-error">{{ $message }}</em>@enderror
                </label>
                <label class="ocsc-field">
                    <span>Message</span>
                    <textarea name="message" rows="5" maxlength="1000" required placeholder="Type your message here…">{{ old('message') }}</textarea>
                    @error('message')<em class="ocsc-error">{{ $message }}</em>@enderror
                </label>
                <button type="submit" class="ocsc-btn-solid ocsc-btn-block">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    SEND TICKET
                </button>
            </form>
        </div>

        <p class="ocsc-foot-note">
            Our support team is ready to assist you with anything you need regarding One Contract.
            <span class="ocsc-inf" aria-hidden="true">∞</span>
        </p>
    </section>

    {{-- Tickets --}}
    <section class="ocsc-card ocsc-tickets-card" id="support-tickets">
        <div class="ocsc-card-head">
            <h2>YOUR <span>TICKETS</span></h2>
            <span class="ocsc-ticket-count">{{ number_format($tickets->total()) }} total</span>
        </div>

        @if ($tickets->count() === 0)
            <div class="ocsc-empty">
                <p>No tickets yet. Submit a request above and our team will assist you.</p>
            </div>
        @else
            <div class="ocsc-tickets-list">
                @foreach ($tickets as $ticket)
                    <article class="ocsc-ticket-row">
                        <div>
                            <strong>{{ $ticket->subject_label }}</strong>
                            <em>{{ $ticket->created_at->format('M d, Y H:i') }}</em>
                        </div>
                        <span class="ocsc-status ocsc-status--{{ $ticket->status }}">{{ ucfirst($ticket->status) }}</span>
                    </article>
                @endforeach
            </div>
            {{ $tickets->links() }}
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const formWrap = document.getElementById('ocsc-form-wrap');
    const toggle = document.getElementById('ocsc-toggle-form');
    const openForm = () => {
        if (!formWrap || !toggle) return;
        formWrap.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        formWrap.querySelector('select, textarea')?.focus();
    };
    toggle?.addEventListener('click', () => {
        if (!formWrap) return;
        const open = formWrap.hidden;
        formWrap.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) formWrap.querySelector('select, textarea')?.focus();
    });
    document.querySelectorAll('a[href="#support-submit"]').forEach((a) => {
        a.addEventListener('click', () => setTimeout(openForm, 50));
    });
    @if ($errors->any() || old('subject') || old('message'))
        openForm();
    @endif

    document.querySelectorAll('[data-ocsc-faq]').forEach((item) => {
        const btn = item.querySelector('.ocsc-faq-btn');
        const panel = item.querySelector('.ocsc-faq-panel');
        btn?.addEventListener('click', () => {
            const open = panel?.hidden;
            document.querySelectorAll('.ocsc-faq-panel').forEach((p) => { p.hidden = true; });
            document.querySelectorAll('.ocsc-faq-btn').forEach((b) => b.setAttribute('aria-expanded', 'false'));
            document.querySelectorAll('.ocsc-faq-item').forEach((i) => i.classList.remove('is-open'));
            if (open && panel) {
                panel.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
                item.classList.add('is-open');
            }
        });
    });
});
</script>
@endpush
