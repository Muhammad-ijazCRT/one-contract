@extends('layouts.app')

@section('title', 'Savings Plan - One-Contract')
@section('meta_description', 'Save between $0.10 and $125.00 per day for 360 successful days and earn 18% reward on your total savings. The One-Contract automated daily savings plan.')

@push('styles')
    @vite(['resources/css/savings-plan.css'])
@endpush

@section('content')
<div class="sp-page">

    {{-- ══════════════════════════════════════════
         HERO
    ══════════════════════════════════════════ --}}
    <section class="sp-hero">
        <div class="sp-hero-bg" aria-hidden="true"></div>
        <div class="container sp-hero-content">
            <span class="section-tag">Savings Plan</span>
            <h1 class="sp-hero-title">Save Daily. Earn More.</h1>
            <p class="sp-hero-tagline">
                Discipline <span class="dot">•</span>
                Consistency <span class="dot">•</span>
                Reward
            </p>
            <p class="sp-hero-desc">
                The One-Contract Savings Plan is an automated daily savings system. Save between
                <strong style="color:#f4c542;">$0.10</strong> and <strong style="color:#f4c542;">$125.00</strong>
                per day for 360 successful days and earn a <strong style="color:#f4c542;">18%</strong>
                fixed reward on your total savings.
            </p>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         HOW IT WORKS
    ══════════════════════════════════════════ --}}
    <section class="sp-section sp-section--light">
        <div class="container">
            <div class="sp-section-header">
                <h2>How the Savings Plan Works</h2>
                <span class="sp-underline"></span>
                <p style="margin-top:0.75rem;">Four simple steps to grow your savings</p>
            </div>

            <div class="sp-steps-grid">
                <article class="sp-step">
                    <div class="sp-step-num">1</div>
                    <div class="sp-step-body">
                        <h3>Set Your Daily Amount</h3>
                        <p>Choose any amount between <strong>$0.10 and $125.00</strong> per day. A small, consistent commitment makes a big difference.</p>
                    </div>
                </article>

                <article class="sp-step">
                    <div class="sp-step-num">2</div>
                    <div class="sp-step-body">
                        <h3>Save Every Day for 100 Days</h3>
                        <p>Funds are automatically deducted from your main wallet each day. No manual action needed — your savings grow on autopilot.</p>
                    </div>
                </article>

                <article class="sp-step">
                    <div class="sp-step-num">3</div>
                    <div class="sp-step-body">
                        <h3>Complete 100 Successful Days</h3>
                        <p>Only successful deductions count. If your wallet has insufficient funds, that day is skipped (not counted) and the plan stays active — deductions resume automatically when funds are available.</p>
                    </div>
                </article>

                <article class="sp-step">
                    <div class="sp-step-num">4</div>
                    <div class="sp-step-body">
                        <h3>Earn 18% on Your Total Savings</h3>
                        <p>After 360 successful days, you earn a <strong>18% reward</strong> on your total contributions. When you claim it to your Main Wallet, a <strong>10% platform fee</strong> is charged from your Main Wallet (on the reward amount).</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         FEATURES
    ══════════════════════════════════════════ --}}
    <section class="sp-section sp-section--dark">
        <div class="container">
            <div class="sp-section-header">
                <h2>Savings Plan Features</h2>
                <span class="sp-underline"></span>
                <p style="margin-top:0.75rem;">Everything you need to build lasting wealth</p>
            </div>

            <div class="sp-features-grid">

                <div class="sp-feature-card">
                    <div class="sp-feature-icon gold">
                        <i class="fa-solid fa-piggy-bank"></i>
                    </div>
                    <h3>Daily Auto-Deduction</h3>
                    <p>Funds are automatically deducted from your main wallet each day — no manual steps, no missed saves.</p>
                </div>

                <div class="sp-feature-card">
                    <div class="sp-feature-icon green">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h3>100-Day Plan</h3>
                    <p>Commit to saving every single day for 360 days. The discipline of consistency is what unlocks the reward.</p>
                </div>

                <div class="sp-feature-card">
                    <div class="sp-feature-icon blue">
                        <i class="fa-solid fa-circle-dollar-sign"></i>
                    </div>
                    <h3>18% Reward</h3>
                    <p>Complete 360 successful days and earn 18% of your total savings. Claiming the reward to Main Wallet charges a 10% platform fee from Main Wallet on the reward amount.</p>
                </div>

                <div class="sp-feature-card">
                    <div class="sp-feature-icon purple">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h3>Withdraw Anytime</h3>
                    <p>Early withdrawal ends the plan immediately. You receive only the amount successfully saved (no reward). Saved funds return to your Main Wallet within 72 hours.</p>
                </div>

                <div class="sp-feature-card">
                    <div class="sp-feature-icon orange">
                        <i class="fa-solid fa-chart-bar"></i>
                    </div>
                    <h3>Track Progress</h3>
                    <p>Monitor your daily savings streak, total accumulated, and days remaining — all from your personal dashboard.</p>
                </div>

                <div class="sp-feature-card">
                    <div class="sp-feature-icon green">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3>Secure &amp; Transparent</h3>
                    <p>Every daily deduction is recorded on-chain and visible in your full transaction history at any time.</p>
                </div>

            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         CALCULATOR
    ══════════════════════════════════════════ --}}
    <section class="sp-section sp-section--light">
        <div class="container">
            <div class="sp-section-header">
                <h2>See What You Can Earn</h2>
                <span class="sp-underline"></span>
                <p style="margin-top:0.75rem;">Choose your daily saving amount and watch your wealth grow</p>
            </div>

            <div class="sp-calc-wrap">

                {{-- Desktop table --}}
                <div class="sp-calc-table-wrap">
                    <table class="sp-calc-table" aria-label="Savings plan earnings calculator">
                        <thead>
                            <tr>
                                <th scope="col">Daily Amount</th>
                                <th scope="col">Duration</th>
                                <th scope="col">Total Saved</th>
                                <th scope="col"><i class="fa-solid fa-star" style="margin-right:0.3rem;"></i> You Earn</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>$0.50</strong> / day</td>
                                <td>360 days</td>
                                <td>$50.00</td>
                                <td>
                                    <span class="sp-earn-badge">
                                        <i class="fa-solid fa-trophy"></i>
                                        $56.75
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>$5.00</strong> / day</td>
                                <td>360 days</td>
                                <td>$500.00</td>
                                <td>
                                    <span class="sp-earn-badge">
                                        <i class="fa-solid fa-trophy"></i>
                                        $567.50
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>$20.00</strong> / day</td>
                                <td>360 days</td>
                                <td>$125.00</td>
                                <td>
                                    <span class="sp-earn-badge">
                                        <i class="fa-solid fa-trophy"></i>
                                        $2,270.00
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Mobile cards --}}
                <div class="sp-calc-cards">

                    <div class="sp-calc-card">
                        <div class="sp-calc-card-header">
                            <span>$0.50 / day</span>
                            <small>Entry level</small>
                        </div>
                        <div class="sp-calc-card-body">
                            <div class="sp-calc-card-stat">
                                <label>Duration</label>
                                <span>360 days</span>
                            </div>
                            <div class="sp-calc-card-stat">
                                <label>Total Saved</label>
                                <span>$50.00</span>
                            </div>
                            <div class="sp-calc-card-stat">
                                <label>You Earn</label>
                                <span class="sp-calc-card-earn">
                                    <i class="fa-solid fa-trophy"></i> $56.75
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="sp-calc-card">
                        <div class="sp-calc-card-header">
                            <span>$5.00 / day</span>
                            <small>Popular</small>
                        </div>
                        <div class="sp-calc-card-body">
                            <div class="sp-calc-card-stat">
                                <label>Duration</label>
                                <span>360 days</span>
                            </div>
                            <div class="sp-calc-card-stat">
                                <label>Total Saved</label>
                                <span>$500.00</span>
                            </div>
                            <div class="sp-calc-card-stat">
                                <label>You Earn</label>
                                <span class="sp-calc-card-earn">
                                    <i class="fa-solid fa-trophy"></i> $567.50
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="sp-calc-card">
                        <div class="sp-calc-card-header">
                            <span>$20.00 / day</span>
                            <small>Maximum</small>
                        </div>
                        <div class="sp-calc-card-body">
                            <div class="sp-calc-card-stat">
                                <label>Duration</label>
                                <span>360 days</span>
                            </div>
                            <div class="sp-calc-card-stat">
                                <label>Total Saved</label>
                                <span>$125.00</span>
                            </div>
                            <div class="sp-calc-card-stat">
                                <label>You Earn</label>
                                <span class="sp-calc-card-earn">
                                    <i class="fa-solid fa-trophy"></i> $2,270.00
                                </span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         FAQ
    ══════════════════════════════════════════ --}}
    <section class="sp-section sp-section--dark">
        <div class="container">
            <div class="sp-section-header">
                <h2>Savings Plan FAQs</h2>
                <span class="sp-underline"></span>
                <p style="margin-top:0.75rem;">Common questions about how the plan works</p>
            </div>

            <div class="sp-faq-list" role="list">

                <div class="sp-faq-item" role="listitem">
                    <button
                        class="sp-faq-btn"
                        aria-expanded="false"
                        aria-controls="sp-faq-1"
                        id="sp-faq-btn-1">
                        What happens if I miss a day?
                        <span class="sp-faq-chevron" aria-hidden="true">
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </button>
                    <div class="sp-faq-body" id="sp-faq-1" role="region" aria-labelledby="sp-faq-btn-1">
                        <p>If your Main Wallet has insufficient funds, no deduction occurs that day. The day is skipped and does not count toward the 360 successful days. Your Savings Plan stays active, and deductions resume automatically when funds become available.</p>
                    </div>
                </div>

                <div class="sp-faq-item" role="listitem">
                    <button
                        class="sp-faq-btn"
                        aria-expanded="false"
                        aria-controls="sp-faq-2"
                        id="sp-faq-btn-2">
                        Can I change my daily amount after starting?
                        <span class="sp-faq-chevron" aria-hidden="true">
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </button>
                    <div class="sp-faq-body" id="sp-faq-2" role="region" aria-labelledby="sp-faq-btn-2">
                        <p>The daily amount is fixed when you set up the plan. You'll need to complete or close the current plan before starting a new one with a different daily saving amount. Choose your amount carefully before activating the plan.</p>
                    </div>
                </div>

                <div class="sp-faq-item" role="listitem">
                    <button
                        class="sp-faq-btn"
                        aria-expanded="false"
                        aria-controls="sp-faq-3"
                        id="sp-faq-btn-3">
                        When do I receive my 18% reward?
                        <span class="sp-faq-chevron" aria-hidden="true">
                            <i class="fa-solid fa-chevron-down"></i>
                        </span>
                    </button>
                    <div class="sp-faq-body" id="sp-faq-3" role="region" aria-labelledby="sp-faq-btn-3">
                        <p>After 360 successful deductions, a 18% reward on your total saved amount becomes available to claim. When you claim it to your Main Wallet, a 10% platform fee is deducted from your Main Wallet (based on the reward). Saved capital returns automatically to Main Wallet within 72 hours after maturity.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ══════════════════════════════════════════
         CTA
    ══════════════════════════════════════════ --}}
    <section class="sp-cta">
        <div class="container">
            <h2>Start Your Savings Journey Today</h2>
            <p>Join thousands of members who are building wealth one day at a time.</p>
            <a
                href="{{ auth()->check() ? route('user.dashboard') : route('register') }}"
                class="sp-cta-btn"
                id="sp-cta-start-btn">
                <i class="fa-solid fa-piggy-bank"></i>
                Create Savings Plan
            </a>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
    // Accordion FAQ behaviour
    document.querySelectorAll('.sp-faq-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var expanded = this.getAttribute('aria-expanded') === 'true';
            var targetId = this.getAttribute('aria-controls');
            var body     = document.getElementById(targetId);

            // Collapse all
            document.querySelectorAll('.sp-faq-btn').forEach(function(b) {
                b.setAttribute('aria-expanded', 'false');
            });
            document.querySelectorAll('.sp-faq-body').forEach(function(el) {
                el.classList.remove('open');
            });

            // Open clicked (toggle)
            if (!expanded) {
                this.setAttribute('aria-expanded', 'true');
                body.classList.add('open');
            }
        });
    });
</script>
@endpush
