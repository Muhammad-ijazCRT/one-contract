@extends('layouts.app')

@section('title', 'Contact Us — One-Contract™')
@section('meta_description', 'Contact One-Contract™ support — available 24/7 for help with deposits, withdrawals, accounts, and general inquiries.')

@push('styles')
    @vite(['resources/css/contact-page.css'])
@endpush

@section('content')
<main class="contact-page">
    <section class="contact-hero">
        <div
            class="contact-hero-bg"
            aria-hidden="true"
            style="background-image: url('{{ asset('images/contact/contact-hero-bg.jpg') }}?v=3');"
        ></div>
        <div class="contact-hero-overlay" aria-hidden="true"></div>
        <div class="container contact-hero-inner">
            <span class="section-tag">Contact Us</span>
            <h1 class="contact-hero-title">Get In Touch</h1>
            <p class="contact-hero-desc">
                Have a question or need assistance? Our support team is here to help you
                24 hours a day, 7 days a week.
            </p>
        </div>
    </section>

    <section class="contact-main">
        <div class="container contact-grid">
            <div class="contact-info">
                <h2>Contact Information</h2>
                <p class="contact-info-intro">Reach us through any of the channels below. We typically respond within a few hours.</p>

                <div class="contact-cards">
                    <div class="contact-card">
                        <div class="contact-card-icon gold">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>
                        </div>
                        <div>
                            <h3>Email</h3>
                            <a href="mailto:{{ $settings->email }}">{{ $settings->email }}</a>
                        </div>
                    </div>

                    <div class="contact-card">
                        <div class="contact-card-icon blue">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <div>
                            <h3>Phone</h3>
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', $settings->phone) }}">{{ $settings->phone }}</a>
                        </div>
                    </div>

                    <div class="contact-card">
                        <div class="contact-card-icon green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        </div>
                        <div>
                            <h3>Support Hours</h3>
                            <p>{{ $settings->support_hours }}</p>
                        </div>
                    </div>

                    <div class="contact-card">
                        <div class="contact-card-icon purple">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <h3>Registered Office</h3>
                            <p>{{ $settings->office_address }}</p>
                        </div>
                    </div>
                </div>

                <div class="contact-social">
                    <h3>Follow Us</h3>
                    @include('partials.social-links', [
                        'settings' => $settings,
                        'wrapClass' => 'contact-social-links',
                        'linkClass' => 'contact-social-btn',
                    ])
                </div>
            </div>

            <div class="contact-form-wrap">
                <div class="contact-form-card">
                    <h2>Send Us a Message</h2>
                    <p class="contact-form-sub">Fill out the form and we'll get back to you as soon as possible.</p>

                    @if (session('status'))
                        <div class="contact-alert contact-alert-success">
                            <p>{{ session('status') }}</p>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="contact-alert contact-alert-error">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.store') }}" class="contact-form">
                        @csrf
                        <div class="contact-form-row">
                            <div class="contact-field">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="John Doe">
                            </div>
                            <div class="contact-field">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com">
                            </div>
                        </div>
                        <div class="contact-field">
                            <label for="subject">Subject</label>
                            <select id="subject" name="subject" required>
                                <option value="" disabled {{ old('subject') ? '' : 'selected' }}>Select a topic</option>
                                <option value="general" {{ old('subject') === 'general' ? 'selected' : '' }}>General Inquiry</option>
                                <option value="account" {{ old('subject') === 'account' ? 'selected' : '' }}>Account Support</option>
                                <option value="deposits" {{ old('subject') === 'deposits' ? 'selected' : '' }}>Deposits</option>
                                <option value="withdrawals" {{ old('subject') === 'withdrawals' ? 'selected' : '' }}>Withdrawals</option>
                                <option value="technical" {{ old('subject') === 'technical' ? 'selected' : '' }}>Technical Issue</option>
                            </select>
                        </div>
                        <div class="contact-field">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="5" required placeholder="How can we help you?">{{ old('message') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-gold btn-block">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
