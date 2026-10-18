<section class="hero hl-hero" id="home">
    <div class="hero-bg hl-hero-bg"></div>
    <div class="container hero-grid hl-hero-grid">
        <div class="hero-content hl-hero-copy">
            <h1 class="hero-title hl-hero-title">
                <span class="hl-hero-line">Grow, Save &amp;</span>
                <span class="hl-hero-earn">Earn More</span>
            </h1>
            <p class="hero-desc hl-hero-desc">
                An <em>exciting</em> <em>ecosystem</em> where finance meets opportunity.
            </p>

            <div class="hero-buttons hl-hero-btns">
                <a href="{{ auth()->check() ? route('user.dashboard') : route('register') }}" class="btn btn-gold btn-hero hl-btn-primary">
                    Get Started Now
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="#services" class="btn btn-outline-gold btn-hero hl-btn-learn">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                    Learn More
                </a>
            </div>
        </div>

        <div class="hero-visual hl-hero-visual">
            <div class="hero-image-wrap hl-hero-glow">
                <img
                    src="{{ asset('images/landing/hero-vault.png') }}"
                    alt="ONE-CONTRACT™ secure crypto vault with infinity vault and crypto coins"
                    class="hero-image hl-hero-img"
                    width="1000"
                    height="1000"
                    loading="eager"
                >
            </div>
        </div>
    </div>
</section>
