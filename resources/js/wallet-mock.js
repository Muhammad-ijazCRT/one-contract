document.addEventListener('DOMContentLoaded', () => {
    initMyWalletPage();
});

function initMyWalletPage() {
    const root = document.querySelector('[data-oc-mwallet]');
    if (!root) return;

    const HIDDEN = '••••••';
    let balanceHidden = false;
    let hideSmall = false;

    const toggleBalanceBtn = root.querySelector('[data-ocmw-toggle-balance]');
    const eyeIcon = root.querySelector('[data-ocmw-eye-icon]');
    const hideSmallBtn = root.querySelector('[data-ocmw-hide-small]');
    const hideLabel = root.querySelector('[data-ocmw-hide-label]');

    const applyBalanceMask = () => {
        root.querySelectorAll('[data-ocmw-mask]').forEach((el) => {
            el.textContent = balanceHidden ? HIDDEN : (el.dataset.value || el.textContent);
        });

        const total = root.querySelector('[data-ocmw-total]');
        if (total) {
            total.textContent = balanceHidden ? `$${HIDDEN}` : `$${total.dataset.value || '0.00'}`;
        }

        const usdt = root.querySelector('[data-ocmw-usdt]');
        if (usdt) {
            usdt.textContent = balanceHidden
                ? `≈ ${HIDDEN} USDT`
                : `≈ ${usdt.dataset.value || '0.00'} USDT`;
        }

        if (eyeIcon) {
            eyeIcon.className = balanceHidden ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        }
    };

    const applySmallFilter = () => {
        root.querySelectorAll('[data-ocmw-asset]').forEach((el) => {
            const isSmall = el.dataset.small === '1' || Number(el.dataset.usd || 0) < 1;
            el.hidden = hideSmall && isSmall;
        });
        hideSmallBtn?.classList.toggle('is-active', hideSmall);
        if (hideLabel) {
            hideLabel.textContent = hideSmall ? 'Show Small Balances' : 'Hide Small Balances';
        }
    };

    toggleBalanceBtn?.addEventListener('click', () => {
        balanceHidden = !balanceHidden;
        applyBalanceMask();
    });

    hideSmallBtn?.addEventListener('click', () => {
        hideSmall = !hideSmall;
        applySmallFilter();
    });
}
