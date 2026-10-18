<footer class="site-footer" id="contact">
    <div class="container footer-grid">
        <div class="footer-brand">
            @include('partials.logo', ['href' => route('home'), 'class' => 'footer-logo'])
            <p class="footer-brand-desc">One-Contract™ is a smart USDT investment platform offering daily rewards, secure withdrawals, and transparent treasury management.</p>
        </div>
        <div class="footer-col footer-col--company">
            <h4>Company</h4>
            <ul>
                <li><a href="{{ route('legal.show', 'treasury-liquidity-notice') }}">Treasury Notice</a></li>
                <li><a href="{{ route('legal.show', 'anti-ponzi-compliance-policy') }}">Anti-Ponzi Policy</a></li>
                <li><a href="{{ route('legal.show', 'scheduled-maintenance-notice') }}">Maintenance Notice</a></li>
            </ul>
        </div>
        <div class="footer-col footer-col--support">
            <h4>Support</h4>
            <ul>
                <li><a href="{{ route('legal.show', 'user-agreement') }}">User Agreement</a></li>
                <li><a href="{{ route('legal.show', 'terms-of-service') }}">Terms</a></li>
                <li><a href="{{ route('legal.show', 'investment-clubs-terms') }}">Schemes Terms</a></li>
                <li><a href="{{ route('legal.show', 'privacy-policy') }}">Privacy</a></li>
                <li><a href="{{ route('legal.show', 'deposit-withdrawal-policy') }}">Deposit Policy</a></li>
                <li><a href="{{ route('legal.show', 'disclaimer') }}">Disclaimer</a></li>
            </ul>
        </div>
        <div class="footer-col footer-col--resource">
            <h4>Resource</h4>
            <ul>
                <li><a href="{{ route('how-it-works') }}">How It Works</a></li>
                <li><a href="{{ route('features') }}">Features</a></li>
                <li><a href="{{ route('faq') }}">FAQ</a></li>
                <li><a href="{{ route('contact') }}">Contact</a></li>
            </ul>
        </div>
        <div class="footer-col footer-col--newsletter">
            <h4>Newsletter</h4>
            <p class="footer-newsletter-text">Subscribe for platform updates and important notices.</p>
            <form class="footer-newsletter-form" action="{{ route('newsletter.subscribe') }}" method="POST">
                @csrf
                <label class="sr-only" for="footer-newsletter-email">Email address</label>
                <div class="footer-newsletter-inline">
                    <input
                        type="email"
                        id="footer-newsletter-email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Email address"
                        required
                        autocomplete="email"
                    >
                    <button type="submit">Subscribe</button>
                </div>
            </form>
            @error('email')
                <p class="footer-newsletter-message footer-newsletter-message--error">{{ $message }}</p>
            @enderror
            @if (session('newsletter_status'))
                <p class="footer-newsletter-message footer-newsletter-message--success">{{ session('newsletter_status') }}</p>
            @endif
        </div>
        <div class="footer-social-bar">
            @include('partials.social-links', [
                'settings' => $contactSettings,
                'wrapClass' => 'social-links footer-social-links',
                'linkClass' => 'social-btn',
            ])
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <p>&copy; {{ date('Y') }} <a href="https://dowhf.com" class="footer-brand-link">dowhf.com</a>. All Rights Reserved.</p>
        </div>
    </div>
</footer>
