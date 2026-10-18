@php
    $minDeposit = isset($minDeposit) ? (float) $minDeposit : 5.0;
@endphp
<section class="hl-join section" id="join">
    <div class="container hl-join-grid">
        {{-- Deposit / calculator card --}}
        <div class="hl-deposit-card calculator-card" id="get-started">
            <div class="hl-deposit-head">
                <h2 class="hl-deposit-title">Deposit Funds</h2>
                <span class="hl-deposit-badge">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    Secure Crypto Checkout
                </span>
            </div>
            <p class="hl-deposit-desc">Enter your amount in USD and continue to our secure crypto payment page. You'll be able to choose your coin and complete the deposit there.</p>

            <div class="calc-body">
                <div class="calc-field">
                    <label for="calc-type" class="calc-label">Select Plan / Portfolio</label>
                    <div class="calc-select-wrap">
                        <select id="calc-type" class="calc-select">
                            <option value="binary">Lion Investment Scheme (0.1% Daily · 35 Days)</option>
                            <option value="crypto">Eagle Investment Scheme (0.1% Daily · 35 Days)</option>
                            <option value="forex">Man Investment Scheme (0.1% Daily · 35 Days)</option>
                            <option value="casino">Ox Investment Scheme (0.1% Daily · 35 Days)</option>
                            <option value="polymarket">Gorilla Investment Scheme (0.1% Daily · 35 Days)</option>
                        </select>
                    </div>
                </div>

                <label for="calc-amount" class="calc-label" id="calc-amount-label">Deposit Amount (USD)</label>
                <div class="calc-input-wrap hl-dep-amount">
                    <span class="hl-dep-prefix">$</span>
                    <input type="number" id="calc-amount" class="calc-input" value="{{ number_format($minDeposit, 2, '.', '') }}" min="0.10" step="0.10" placeholder="{{ number_format($minDeposit, 2) }}">
                    <span class="calc-currency">USD</span>
                </div>

                <div class="hl-dep-notes">
                    <div class="hl-dep-note">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <div>
                            <strong>Choose your coin next</strong>
                            <p>After continue, pick BTC, ETH, USDT and other supported coins.</p>
                        </div>
                    </div>
                    <div class="hl-dep-note">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        <div>
                            <strong>Secure hosted checkout</strong>
                            <p>Deposits are processed through our encrypted crypto gateway.</p>
                        </div>
                    </div>
                </div>

                <div class="calc-results" id="investment-results">
                    <div class="calc-result">
                        <span class="calc-result-label">Daily Profit</span>
                        <span class="calc-result-value" id="daily-profit">$0.00</span>
                    </div>
                    <div class="calc-result">
                        <span class="calc-result-label">35-Day Profit</span>
                        <span class="calc-result-value" id="weekly-profit">$0.00</span>
                    </div>
                    <div class="calc-result">
                        <span class="calc-result-label">30-Day Profit</span>
                        <span class="calc-result-value" id="monthly-profit">$0.00</span>
                    </div>
                </div>

                <div class="hl-dep-footer">
                    <div class="hl-dep-cta-row">
                        <span class="hl-dep-coins" aria-hidden="true">
                            <i class="fa-brands fa-bitcoin"></i>
                            <i class="fa-brands fa-ethereum"></i>
                            <span>₮</span>
                        </span>
                        <a href="{{ auth()->check() ? route('user.deposit.index') : route('login') }}" class="btn btn-gold hl-dep-cta">
                            Continue to Crypto Payment
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                    <p class="hl-dep-secured">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                        Secured By Crypto Gateway
                    </p>
                </div>
            </div>
        </div>

        {{-- Why join: title → image+list → full-width CTA --}}
        <aside class="hl-why-join">
            <h2 class="hl-why-title">Why Join One-Contract™?</h2>

            <div class="hl-why-body">
                <div class="hl-why-visual">
                    <img
                        src="{{ asset('images/landing/why-soldier.png') }}"
                        alt=""
                        class="hl-why-img"
                        loading="lazy"
                        decoding="async"
                    >
                </div>
                <ul class="hl-why-list">
                    <li>
                        <span class="hl-why-ico" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                        <div>
                            <strong>Secure &amp; Encrypted</strong>
                            <p>Bank-level security for your peace of mind.</p>
                        </div>
                    </li>
                    <li>
                        <span class="hl-why-ico" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span>
                        <div>
                            <strong>Trusted Platform</strong>
                            <p>Built with transparency and reliability.</p>
                        </div>
                    </li>
                    <li>
                        <span class="hl-why-ico" aria-hidden="true"><i class="fa-solid fa-headset"></i></span>
                        <div>
                            <strong>24/7 Customer Support</strong>
                            <p>We're here to help whenever you need us.</p>
                        </div>
                    </li>
                    <li>
                        <span class="hl-why-ico" aria-hidden="true"><i class="fa-solid fa-star"></i></span>
                        <div>
                            <strong>Trusted &amp; Transparent</strong>
                            <p>Fully transparent operations you can trust.</p>
                        </div>
                    </li>
                </ul>
            </div>

            <a href="{{ auth()->check() ? route('user.dashboard') : route('register') }}" class="btn btn-gold hl-why-btn">
                Get Started Now
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </aside>
    </div>
</section>
