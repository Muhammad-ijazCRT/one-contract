import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/auth-onboarding.css', 'resources/css/home-onboarding-landing.css', 'resources/css/about.css', 'resources/css/how-it-works.css', 'resources/css/features.css', 'resources/css/faq-page.css', 'resources/css/contact-page.css', 'resources/css/legal-page.css', 'resources/css/dashboard.css', 'resources/css/dashboard-home-mock.css', 'resources/css/profile-mock.css', 'resources/css/global-chat.css', 'resources/css/wallet-mock.css', 'resources/css/rewards-mock.css', 'resources/css/rewards-v2.css', 'resources/css/deposit-mock.css', 'resources/css/deposit-v2.css', 'resources/css/game-arena.css', 'resources/css/investment-clubs.css', 'resources/css/savings-plan.css', 'resources/css/prediction.css', 'resources/css/azubogu.css', 'resources/css/rpsv.css', 'resources/css/support-mock.css', 'resources/css/select-currency.css', 'resources/css/convert-mock.css', 'resources/css/user-notifications.css', 'resources/js/app.js', 'resources/js/dashboard.js', 'resources/js/global-chat.js', 'resources/js/wallet-mock.js', 'resources/js/deposit-mock.js', 'resources/js/deposit-v2.js', 'resources/js/convert-mock.js', 'resources/js/auth.js', 'resources/js/profile-avatar-crop.js', 'resources/js/home-onboarding-carousel.js', 'resources/js/prediction.js', 'resources/js/azubogu.js', 'resources/js/rpsv.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
