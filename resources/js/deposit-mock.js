/**
 * Deposit UI — USD amount in, live crypto equivalent out.
 */
(() => {
    const root = document.querySelector('[data-oc-deposit]');
    if (!root) return;

    const methodButtons = [...root.querySelectorAll('[data-ocdep-method]')];
    const panels = [...root.querySelectorAll('[data-ocdep-panel]')];

    const setMethod = (method) => {
        methodButtons.forEach((btn) => {
            const active = btn.dataset.ocdepMethod === method;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach((panel) => {
            panel.classList.toggle('is-visible', panel.dataset.ocdepPanel === method);
        });
    };

    methodButtons.forEach((btn) => {
        btn.addEventListener('click', () => setMethod(btn.dataset.ocdepMethod));
    });

    const amountInput = root.querySelector('[data-ocdep-amount]');
    const currencySelect = root.querySelector('[data-ocdep-currency]');
    const networkSelect = root.querySelector('[data-ocdep-network]');
    const currencyIcon = root.querySelector('[data-ocdep-currency-icon]');
    const networkIcon = root.querySelector('[data-ocdep-network-icon]');
    const receiveEl = root.querySelector('[data-ocdep-receive]');
    const receiveCode = root.querySelector('[data-ocdep-receive-code]');
    const rateEl = root.querySelector('[data-ocdep-rate]');
    const maxBtn = root.querySelector('[data-ocdep-max]');
    const chips = [...root.querySelectorAll('[data-ocdep-chip]')];
    const min = Number(root.dataset.min || 10);
    const maxQuick = 500;
    const ratesUrl = root.dataset.ratesUrl || '';

    let usdRates = {};
    try {
        usdRates = JSON.parse(root.dataset.usdRates || '{}');
    } catch {
        usdRates = {};
    }

    let decimalsMap = {};
    try {
        decimalsMap = JSON.parse(root.dataset.decimals || '{}');
    } catch {
        decimalsMap = {};
    }

    const formatUsd = (n) =>
        Number(n || 0).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

    const formatCrypto = (n, decimals) => {
        if (!Number.isFinite(n) || n <= 0) return '—';
        const d = Math.max(2, Math.min(8, Number(decimals) || 8));
        return n.toLocaleString(undefined, {
            minimumFractionDigits: Math.min(2, d),
            maximumFractionDigits: d,
        });
    };

    const selectedCode = () => currencySelect?.value || 'USDT';

    const usdPerUnit = (code) => Number(usdRates[code] || 0);

    const cryptoForUsd = (usd, code) => {
        const per = usdPerUnit(code);
        if (per <= 0 || usd <= 0) return 0;
        return usd / per;
    };

    const syncReceive = () => {
        const usd = Number(amountInput?.value || 0);
        const code = selectedCode();
        const per = usdPerUnit(code);
        const cryptoAmt = cryptoForUsd(usd, code);
        const decimals = decimalsMap[code] ?? 8;

        if (receiveCode) receiveCode.textContent = code;
        if (receiveEl) {
            receiveEl.textContent = per > 0 ? formatCrypto(cryptoAmt, decimals) : '—';
        }
        if (rateEl) {
            rateEl.textContent = per > 0
                ? `1 ${code} ≈ $${formatUsd(per)} · $${formatUsd(usd)} USD ≈ ${formatCrypto(cryptoAmt, decimals)} ${code}`
                : 'Live USD rate unavailable. You can still proceed — NOWPayments will quote the exact coin amount.';
        }

        chips.forEach((chip) => {
            const chipVal = Number(chip.dataset.ocdepChip);
            chip.classList.toggle('is-active', Math.abs(chipVal - usd) < 0.001);
        });
    };

    const setAmount = (val) => {
        if (!amountInput) return;
        amountInput.value = Number(val).toFixed(2);
        syncReceive();
    };

    const rebuildNetworks = () => {
        if (!networkSelect || !currencySelect) return;
        const opt = currencySelect.selectedOptions?.[0];
        let networks = [];
        try {
            networks = JSON.parse(opt?.dataset?.networks || '[]');
        } catch {
            networks = [];
        }

        networkSelect.innerHTML = '';
        networks.forEach((net, idx) => {
            const option = document.createElement('option');
            option.value = net.id;
            option.textContent = net.label;
            option.dataset.icon = net.icon || '';
            if (idx === 0) option.selected = true;
            networkSelect.appendChild(option);
        });
        syncNetworkIcon();
    };

    const syncNetworkIcon = () => {
        const opt = networkSelect?.selectedOptions?.[0];
        const icon = opt?.dataset?.icon;
        if (icon && networkIcon) networkIcon.src = icon;
    };

    const syncCurrencyIcons = () => {
        const opt = currencySelect?.selectedOptions?.[0];
        const icon = opt?.dataset?.icon;
        if (icon && currencyIcon) currencyIcon.src = icon;
        syncReceive();
        rebuildNetworks();
    };

    const applyRatePayload = (payload) => {
        if (!payload || typeof payload !== 'object') return;
        if (payload.balances_keyed && typeof payload.balances_keyed === 'object') {
            usdRates = payload.balances_keyed;
            return;
        }
        if (payload.pairs && typeof payload.pairs === 'object') {
            const next = { ...usdRates };
            Object.entries(payload.pairs).forEach(([key, value]) => {
                if (key.endsWith('_USD')) {
                    next[key.replace(/_USD$/, '')] = Number(value) || 0;
                }
            });
            usdRates = next;
        }
    };

    const refreshRates = async () => {
        if (!ratesUrl) return;
        try {
            const res = await fetch(ratesUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            applyRatePayload(await res.json());
            syncReceive();
        } catch {
            // Keep last known rates.
        }
    };

    chips.forEach((chip) => {
        chip.addEventListener('click', () => setAmount(chip.dataset.ocdepChip));
    });

    maxBtn?.addEventListener('click', () => setAmount(Math.max(min, maxQuick)));
    amountInput?.addEventListener('input', syncReceive);
    currencySelect?.addEventListener('change', syncCurrencyIcons);
    networkSelect?.addEventListener('change', syncNetworkIcon);

    syncCurrencyIcons();
    syncReceive();
    refreshRates();
})();
