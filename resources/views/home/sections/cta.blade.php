<section class="cta-banner hl-cta">
    <div class="container cta-grid hl-cta-grid">
        <div class="cta-image-wrap">
            <img src="{{ asset('images/logo.png') }}" alt="ONE CONTRACT™" class="cta-image hl-cta-logo oc-brand-logo" loading="lazy">
        </div>
        <div class="cta-content">
            <h2>Join One-Contract™ Today</h2>
            <p>Start your journey to financial freedom.</p>
        </div>
        <div class="cta-action">
            <a href="{{ auth()->check() ? route('user.clubs.index') : route('register') }}" class="btn btn-gold btn-lg">
                Get Started Now
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</section>
