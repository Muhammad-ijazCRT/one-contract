@php
    $step = fn ($file) => asset('images/landing/'.$file);
@endphp
<section class="section hl-how" id="how-it-works">
    <div class="container hl-how-grid">
        <div class="hl-how-main">
            <h2 class="hl-how-title">How It Works</h2>
            <div class="hl-flow" role="list">
                <div class="hl-flow-step" role="listitem">
                    <img src="{{ $step('step-account.png') }}" alt="" class="hl-flow-img" loading="lazy" decoding="async">
                    <h3>Create Account</h3>
                    <p>Sign up in minutes with your email and secure your account.</p>
                </div>
                <span class="hl-flow-chevron" aria-hidden="true">&raquo;</span>
                <div class="hl-flow-step" role="listitem">
                    <img src="{{ $step('step-deposit.png') }}" alt="" class="hl-flow-img" loading="lazy" decoding="async">
                    <h3>Make a Deposit</h3>
                    <p>Deposit crypto and start earning rewards instantly.</p>
                </div>
                <span class="hl-flow-chevron" aria-hidden="true">&raquo;</span>
                <div class="hl-flow-step" role="listitem">
                    <img src="{{ $step('step-earn.png') }}" alt="" class="hl-flow-img" loading="lazy" decoding="async">
                    <h3>Earn Daily</h3>
                    <p>Watch your balance grow automatically every single day.</p>
                </div>
                <span class="hl-flow-chevron" aria-hidden="true">&raquo;</span>
                <div class="hl-flow-step" role="listitem">
                    <img src="{{ $step('step-withdraw.png') }}" alt="" class="hl-flow-img" loading="lazy" decoding="async">
                    <h3>Withdraw Reward</h3>
                    <p>Withdraw profits on schedule or manage capital with clear rules.</p>
                </div>
            </div>
        </div>

        <aside class="hl-choose-panel" id="features">
            <div class="hl-choose-copy">
                <h2 class="hl-how-title">Why Choose One-Contract™?</h2>
                <p class="hl-choose-sub">Built For Smart Investors</p>
                <ul class="hl-choose-list">
                    <li><span class="hl-check" aria-hidden="true">✓</span> 3-Level Referral Commission system</li>
                    <li><span class="hl-check" aria-hidden="true">✓</span> 100% Capital Protection policy</li>
                    <li><span class="hl-check" aria-hidden="true">✓</span> No Lock-in Period — withdraw anytime</li>
                    <li><span class="hl-check" aria-hidden="true">✓</span> 35-Day Profit Withdrawal cycle</li>
                    <li><span class="hl-check" aria-hidden="true">✓</span> 24/7 Customer Support available</li>
                </ul>
            </div>
            <div class="hl-choose-visual">
                <img
                    src="{{ asset('images/landing/why-shield.png') }}"
                    alt=""
                    class="hl-choose-img"
                    loading="lazy"
                    decoding="async"
                >
            </div>
        </aside>
    </div>
</section>
