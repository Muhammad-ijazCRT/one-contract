@php
    $authDash = auth()->check();
    $img = fn ($file) => asset('images/landing/'.$file);
@endphp
<section class="hl-services" id="services" aria-label="Platform services">
    <div class="container hl-services-grid">
        <article class="hl-svc hl-svc--green">
            <div class="hl-svc-media">
                <img src="{{ $img('svc-invest.png') }}" alt="" loading="lazy" decoding="async">
            </div>
            <h3>Invest</h3>
            <p>Grow capital with flexible investment schemes and daily rewards.</p>
            <a href="{{ $authDash ? route('user.clubs.index') : route('register') }}" class="hl-svc-btn">Invest Now <span aria-hidden="true">→</span></a>
        </article>

        <article class="hl-svc hl-svc--purple">
            <div class="hl-svc-media">
                <img src="{{ $img('svc-betting.png') }}" alt="" loading="lazy" decoding="async">
            </div>
            <h3>Betting</h3>
            <p>Play prediction markets and games for bigger wins in one place.</p>
            <a href="{{ $authDash ? route('user.prediction.index') : route('register') }}" class="hl-svc-btn">Bet Now <span aria-hidden="true">→</span></a>
        </article>

        <article class="hl-svc hl-svc--red">
            <div class="hl-svc-media">
                <img src="{{ $img('svc-rewards.png') }}" alt="" loading="lazy" decoding="async">
            </div>
            <h3>Rewards</h3>
            <p>Track product, referral, and gaming rewards — then claim with ease.</p>
            <a href="{{ $authDash ? route('user.rewards.claim') : route('register') }}" class="hl-svc-btn">View Rewards <span aria-hidden="true">→</span></a>
        </article>
    </div>
</section>
