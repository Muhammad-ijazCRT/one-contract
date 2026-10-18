document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initHeaderScroll();
    initHeaderProfileDropdown();
    initCalculator();
    initSmoothNav();
    initPasswordToggle();
    initReadMoreToggles();
});

function initMobileNav() {
    const toggle = document.getElementById('mobile-toggle');
    const nav = document.getElementById('mobile-nav');

    if (!toggle || !nav) return;

    toggle.addEventListener('click', () => {
        const isOpen = nav.classList.toggle('open');
        toggle.classList.toggle('open', isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
        nav.setAttribute('aria-hidden', String(!isOpen));

        document.getElementById('header-profile-dropdown')?.classList.remove('open');
        document.getElementById('header-profile-toggle')?.setAttribute('aria-expanded', 'false');
    });

    nav.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            nav.classList.remove('open');
            toggle.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
            nav.setAttribute('aria-hidden', 'true');
        });
    });
}

function initHeaderScroll() {
    const header = document.getElementById('site-header');
    if (!header) return;

    window.addEventListener('scroll', () => {
        header.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });
}

function initHeaderProfileDropdown() {
    const dropdown = document.getElementById('header-profile-dropdown');
    const toggle = document.getElementById('header-profile-toggle');

    if (!dropdown || !toggle) return;

    const close = () => {
        dropdown.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(isOpen));
    });

    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
}

function initCalculator() {
    const typeSelect = document.getElementById('calc-type');
    const input = document.getElementById('calc-amount');
    const label = document.getElementById('calc-amount-label');

    const investmentResults = document.getElementById('investment-results');
    const dailyEl = document.getElementById('daily-profit');
    const weeklyEl = document.getElementById('weekly-profit');
    const monthlyEl = document.getElementById('monthly-profit');

    if (!input || !investmentResults || !dailyEl || !weeklyEl || !monthlyEl) return;

    const rates = {
        'binary': 0.001,
        'crypto': 0.001,
        'forex': 0.001,
        'casino': 0.001,
        'polymarket': 0.001
    };

    const format = (value) => `$${value.toFixed(2)}`;

    const calculate = () => {
        const amount = Math.max(0, parseFloat(input.value) || 0);
        const type = typeSelect ? typeSelect.value : 'binary';

        if (label) {
            label.textContent = 'Enter Investment Amount (USDT)';
        }
        investmentResults.style.display = 'grid';

        const rate = rates[type] || 0.001;
        dailyEl.textContent = format(amount * rate);
        weeklyEl.textContent = format(amount * rate * 35);
        monthlyEl.textContent = format(amount * rate * 30);
    };

    if (typeSelect) typeSelect.addEventListener('change', calculate);
    input.addEventListener('input', calculate);
    calculate();
}

function initSmoothNav() {
    const links = document.querySelectorAll('a[href^="#"]');

    links.forEach((link) => {
        link.addEventListener('click', (e) => {
            const targetId = link.getAttribute('href');
            if (!targetId || targetId === '#') return;

            const target = document.querySelector(targetId);
            if (!target) return;

            e.preventDefault();
            const headerHeight = document.getElementById('site-header')?.offsetHeight ?? 72;
            const top = target.getBoundingClientRect().top + window.scrollY - headerHeight;

            window.scrollTo({ top, behavior: 'smooth' });
        });
    });

    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link, .mobile-nav-link');

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    navLinks.forEach((link) => {
                        link.classList.toggle('active', link.getAttribute('href') === `#${id}`);
                    });
                }
            });
        },
        { rootMargin: '-40% 0px -50% 0px' }
    );

    sections.forEach((section) => observer.observe(section));
}

function initPasswordToggle() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const field = button.closest('.password-field, .oc-login-field-inner');
        const input = field?.querySelector('input[type="password"], input[type="text"]');

        if (!input) return;

        button.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.classList.toggle('is-visible', !isVisible);
            button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');

            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', isVisible);
                icon.classList.toggle('fa-eye-slash', !isVisible);
            }
        });
    });
}

function initReadMoreToggles() {
    document.querySelectorAll('[data-read-toggle]').forEach((root) => {
        const content = root.querySelector('[data-read-more-content]');
        const button = root.querySelector('[data-read-more-button]');
        const dots = root.querySelector('[data-read-more-dots]');

        if (!content || !button) return;

        button.addEventListener('click', () => {
            const isExpanded = button.getAttribute('aria-expanded') === 'true';

            content.hidden = isExpanded;
            if (dots) dots.hidden = !isExpanded;
            button.setAttribute('aria-expanded', String(!isExpanded));
            button.textContent = isExpanded ? 'Read more' : 'Less than';
        });
    });
}
