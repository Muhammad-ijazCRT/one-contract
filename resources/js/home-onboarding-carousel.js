/**
 * Home onboarding — 3 full slides with unique content, auto-play, dots + skip.
 */
const SLIDE_COUNT = 3;
const AUTO_MS = 4500;

export function initHomeOnboardingCarousel() {
    const slider = document.querySelector('[data-onb-slider]');
    const track = document.querySelector('[data-onb-track]');
    const dotsWrap = document.querySelector('[data-onb-dots]');
    if (!slider || !track || !dotsWrap) return;

    const slides = [...track.querySelectorAll('[data-onb-slide]')];
    const dots = [...dotsWrap.querySelectorAll('[data-onb-dot]')];
    if (slides.length !== SLIDE_COUNT || dots.length !== SLIDE_COUNT) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let current = 0;
    let timer = null;
    let paused = false;
    let touchStartX = 0;
    let touchDeltaX = 0;

    const setActiveDot = (index) => {
        current = ((index % SLIDE_COUNT) + SLIDE_COUNT) % SLIDE_COUNT;
        dots.forEach((dot, i) => {
            const active = i === current;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    };

    const setActiveSlide = (index) => {
        const next = ((index % SLIDE_COUNT) + SLIDE_COUNT) % SLIDE_COUNT;
        setActiveDot(next);
        track.style.transform = `translateX(-${next * 100}%)`;

        slides.forEach((slide, i) => {
            const active = i === next;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
        });
    };

    const goToSlide = (index) => {
        setActiveSlide(index);
    };

    const stopAuto = () => {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    };

    const startAuto = () => {
        stopAuto();
        if (reducedMotion || paused) return;

        timer = setInterval(() => {
            if (paused) return;
            goToSlide(current + 1);
        }, AUTO_MS);
    };

    const pauseBriefly = () => {
        paused = true;
        stopAuto();
        window.setTimeout(() => {
            paused = false;
            startAuto();
        }, AUTO_MS * 1.35);
    };

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            goToSlide(i);
            pauseBriefly();
        });
    });

    slider.addEventListener('pointerdown', (event) => {
        touchStartX = event.clientX;
        touchDeltaX = 0;
        paused = true;
        stopAuto();
    });

    slider.addEventListener('pointermove', (event) => {
        if (!paused) return;
        touchDeltaX = event.clientX - touchStartX;
    });

    slider.addEventListener('pointerup', () => {
        const threshold = 48;
        if (touchDeltaX <= -threshold) {
            goToSlide(current + 1);
        } else if (touchDeltaX >= threshold) {
            goToSlide(current - 1);
        }
        touchDeltaX = 0;
        pauseBriefly();
    });

    slider.addEventListener('pointercancel', pauseBriefly);

    const skipBtn = document.querySelector('[data-onb-skip]');
    if (skipBtn) {
        skipBtn.addEventListener('click', (event) => {
            if (current < SLIDE_COUNT - 1) {
                event.preventDefault();
                goToSlide(current + 1);
                pauseBriefly();
            }
        });
    }

    setActiveSlide(0);
    startAuto();
}

document.addEventListener('DOMContentLoaded', initHomeOnboardingCarousel);
