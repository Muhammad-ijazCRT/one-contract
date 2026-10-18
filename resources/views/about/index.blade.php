@extends('layouts.app')

@section('title', 'About Us - One-Contract')
@section('meta_description', 'Learn about One-Contract - a private digital asset platform for USDT deposits. Build, grow, and earn with transparency and security.')

@push('styles')
    @vite(['resources/css/about.css'])
@endpush

@section('content')
<main class="about-page">
    <section class="about-hero">
        <div class="about-hero-bg"></div>
        <div class="container about-hero-grid">
            <div class="about-hero-content">
                <span class="section-tag">About Us</span>
                <h1 class="about-hero-title">About One-Contract</h1>
                <p class="about-hero-tagline">Build <span class="dot">•</span> Grow <span class="dot">•</span> Earn</p>
                <p class="about-hero-desc">
                    One-Contract is a private digital asset platform built to provide a simple,
                    transparent, and secure way to earn daily rewards on your USDT deposits.
                </p>
            </div>
            <div class="about-hero-visual">
                <img src="{{ asset('images/about/hero-usdt-shield.png') }}" alt="USDT secure investment illustration" class="about-hero-image" width="560" height="420" loading="eager">
            </div>
        </div>
    </section>

    <div class="about-light">
        <section class="about-highlights">
            <div class="container">
                <div class="about-highlights-grid">
                    <div class="about-highlight-item">
                        <div class="about-highlight-icon">
                            <img src="{{ asset('images/about/chart-highlight.png') }}" alt="Rising chart icon" width="32" height="32" loading="lazy" style="width:70px;">
                        </div>
                        <div>
                            <strong>1% Daily Reward</strong>
                            <span>Earn a fixed 1% daily reward on your active capital with no compounding.</span>
                        </div>
                    </div>
                    <div class="about-highlight-item">
                        <div class="about-highlight-icon green">
                            @include('about.partials.icon', ['name' => 'calendar'])
                        </div>
                        <div>
                            <strong>Withdraw Every 7 Days</strong>
                            <span>Request withdrawals every 7 days after rewards are processed.</span>
                        </div>
                    </div>
                    <div class="about-highlight-item">
                        <div class="about-highlight-icon purple">
                            @include('about.partials.icon', ['name' => 'users'])
                        </div>
                        <div>
                            <strong>3-Level Referral</strong>
                            <span>Earn referral commissions across 3 levels: 5% - 3% - 2%.</span>
                        </div>
                    </div>
                    <div class="about-highlight-item">
                        <div class="about-highlight-icon orange">
                            @include('about.partials.icon', ['name' => 'shield-check'])
                        </div>
                        <div>
                            <strong>Secure &amp; Transparent</strong>
                            <span>Bank-grade security, real-time data, and full transparency you can trust.</span>
                        </div>
                    </div>
                    <div class="about-highlight-item">
                        <div class="about-highlight-icon blue">
                            @include('about.partials.icon', ['name' => 'headset'])
                        </div>
                        <div>
                            <strong>24/7 Support</strong>
                            <span>Our dedicated support team is available around the clock.</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-mvv">
            <div class="container">
                <div class="about-mvv-grid">
                    <article class="about-mvv-card">
                        <div class="about-mvv-head">
                            <div class="about-mvv-icon">
                            @include('about.partials.icon', ['name' => 'target'])
                        </div>
                        <h2>Our Mission</h2>
                        </div>
                        <p>To create a trusted global digital asset platform that delivers sustainable value through innovation, transparency, disciplined treasury management, and responsible business operations.</p>
                        <div class="about-mvv-visual"><img src="{{ asset('images/about/mission.png') }}" alt="Mission - reaching new heights" loading="lazy"></div>
                    </article>

                    <article class="about-mvv-card">
                        <div class="about-mvv-head">
                            <div class="about-mvv-icon">
                                @include('about.partials.icon', ['name' => 'eye'])
                            </div>
                            <h2>Our Vision</h2>
                        </div>
                        <p>To become a globally recognized blockchain and digital asset company, providing secure, innovative, and sustainable financial solutions for individuals and businesses worldwide.</p>
                        <div class="about-mvv-visual"><img src="{{ asset('images/about/vision.png') }}" alt="Vision - global digital network" loading="lazy"></div>
                    </article>

                    <article class="about-mvv-card about-mvv-card--values">
                        <div class="about-mvv-head">
                            <div class="about-mvv-icon about-mvv-icon--diamond">
                                @include('about.partials.icon', ['name' => 'diamond'])
                            </div>
                            <h2>Our Core Values</h2>
                        </div>
                        <div class="about-values-body">
                            <ul class="about-values-list">
                                <li><span class="about-check">✓</span> Transparency</li>
                                <li><span class="about-check">✓</span> Integrity</li>
                                <li><span class="about-check">✓</span> Innovation</li>
                                <li><span class="about-check">✓</span> Security</li>
                                <li><span class="about-check">✓</span> Sustainability</li>
                                <li><span class="about-check">✓</span> Customer commitment</li>
                                <li><span class="about-check">✓</span> Responsible risk management</li>
                                <li><span class="about-check">✓</span> Regulatory compliance</li>
                            </ul>
                            <div class="about-values-visual"><img src="{{ asset('images/about/core-values.png') }}" alt="Core values - security and community" loading="lazy"></div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="about-business">
            <div class="container">
                <div class="about-business-grid">
                    <div class="about-business-content">
                        <div class="about-section-head">
                            <div class="about-mvv-icon">
                                @include('about.partials.icon', ['name' => 'briefcase'])
                            </div>
                            <h2>Business Activities</h2>
                        </div>
                        <p class="about-business-intro">One-Contract is focused on blockchain technology, digital assets, and financial technology. The company intends to engage in lawful activities including:</p>
                        <div class="about-activities-grid">
                            <ul>
                                <li><span class="about-check green">✓</span> Cryptocurrency trading and digital asset portfolio management</li>
                                <li><span class="about-check green">✓</span> Blockchain technology development</li>
                                <li><span class="about-check green">✓</span> Digital asset investment management</li>
                                <li><span class="about-check green">✓</span> Token creation and ecosystem development</li>
                                <li><span class="about-check green">✓</span> NFT creation and marketplace development</li>
                                <li><span class="about-check green">✓</span> Cryptocurrency treasury and liquidity management</li>
                            </ul>
                            <ul>
                                <li><span class="about-check green">✓</span> Prediction market trading (where legally permitted)</li>
                                <li><span class="about-check green">✓</span> Binary trading strategies</li>
                                <li><span class="about-check green">✓</span> Controlled-risk betting and gaming strategies</li>
                                <li><span class="about-check green">✓</span> Smart contract development</li>
                                <li><span class="about-check green">✓</span> Blockchain software and platform development</li>
                                <li><span class="about-check green">✓</span> Digital payment solutions</li>
                            </ul>
                            <ul>
                                <li><span class="about-check green">✓</span> Financial technology (FinTech) services</li>
                                <li><span class="about-check green">✓</span> Blockchain research and innovation</li>
                                <li><span class="about-check green">✓</span> Cryptocurrency education and training</li>
                                <li><span class="about-check green">✓</span> Investment in blockchain infrastructure and emerging technologies</li>
                                <li><span class="about-check green">✓</span> Any other lawful cryptocurrency, blockchain, digital asset, or fintech activities permitted under applicable laws and regulations</li>
                            </ul>
                        </div>
                    </div>
                    <div class="about-business-visual">
                        <img src="{{ asset('images/about/business.png') }}" alt="Cryptocurrency trading and technology" class="about-business-image" loading="lazy">
                    </div>
                </div>
            </div>
        </section>

        <section class="about-corporate">
            <div class="container">
                <div class="about-corporate-grid">
                <article class="about-corporate-card about-corporate-card--security">
                    <div class="about-corporate-body">
                        <div class="about-corporate-text">
                            <div class="about-corporate-head">
                                <div class="about-corporate-icon">
                                    @include('about.partials.icon', ['name' => 'shield-check'])
                                </div>
                                <h2>Commitment to Security</h2>
                            </div>
                            <p>The security of participant assets and data is a core priority. One-Contract is committed to implementing industry-standard security practices, including encrypted communications, secure account authentication, treasury controls, transaction monitoring, and ongoing system improvements to help protect the platform and its users.</p>
                        </div>
                            <div class="about-corporate-visual">
                                <img src="{{ asset('images/about/security.png') }}" alt="Data security and protection" loading="lazy">
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="about-disclaimer">
            <div class="container">
                <div class="about-disclaimer-grid">
                    <div class="about-disclaimer-item">
                        <div class="about-disclaimer-icon">
                            <img src="{{ asset('images/about/disclaimer-info.png') }}" alt="Disclaimer icon" width="32" height="32" loading="lazy" style="width: 70px;">
                        </div>
                        <div>
                            <h3>Disclaimer</h3>
                            <p>One-Contract is a private digital asset platform. It is not a bank, deposit-taking institution, or government-backed investment scheme.</p>
                        </div>
                    </div>
                    <div class="about-disclaimer-item">
                        <div class="about-disclaimer-icon">
                            <img src="{{ asset('images/about/age-18.png') }}" alt="18 plus icon" width="32" height="32" loading="lazy" style="width: 70px;">
                        </div>
                        <div>
                            <h3>Age Requirement</h3>
                            <p>Participation is voluntary and limited to individuals who are at least 18 years of age and who are legally permitted to participate under the laws of their jurisdiction.</p>
                        </div>
                    </div>
                    <div class="about-disclaimer-item about-disclaimer-item--risk">
                        <div class="about-disclaimer-icon">
                            <img src="{{ asset('images/about/investment-risk.png') }}" alt="Investment risk icon" width="32" height="32" loading="lazy" style="width: 70px;">
                        </div>
                        <div class="about-risk-copy" data-read-toggle>
                            <h3>Investment Risk</h3>
                            <p>Digital asset markets involve risk. While One-Contract™ aims to operate responsibly and maintain prudent treasury management, participants should understand that rewards and business performance are not guaranteed and may be impacted by economic and market conditions, and applicable laws.</p>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>
@endsection
