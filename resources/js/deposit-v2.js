/**
 * Deposit V2 Controller Script
 * Handles Tab Switching (Crypto / Fiat), Amount Sync, Live Rates, Quick Chips & Calculation Receipts
 */
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-ocdep2-root]');
    if (!root) return;

    // Elements
    const tabButtons = root.querySelectorAll('[data-ocdep2-tab]');
    const heroCrypto = root.querySelector('[data-hero-crypto]');
    const heroFiat = root.querySelector('[data-hero-fiat]');
    const panelCrypto = root.querySelector('[data-panel-crypto]');
    const panelFiat = root.querySelector('[data-panel-fiat]');

    // Crypto inputs
    const cryptoAmountInput = root.querySelector('[data-crypto-amount]');
    const cryptoCoinSelect = root.querySelector('[data-crypto-coin-select]');
    const cryptoCoinCode = root.querySelector('[data-crypto-coin-code]');
    const cryptoCoinName = root.querySelector('[data-crypto-coin-name]');
    const cryptoCoinIcon = root.querySelector('[data-crypto-coin-icon]');
    const cryptoNetIcon = root.querySelector('[data-crypto-net-icon]');
    const cryptoNetTitle = root.querySelector('[data-crypto-net-title]');
    const cryptoSendAmt = root.querySelector('[data-crypto-send-amt]');
    const cryptoRateDisplay = root.querySelector('[data-crypto-rate-display]');
    const cryptoChips = root.querySelectorAll('[data-crypto-chip]');

    // Fiat inputs
    const fiatAmountInput = root.querySelector('[data-fiat-amount]');
    const fiatMethodCards = root.querySelectorAll('[data-fiat-method]');
    const fiatReceiveAmt = root.querySelector('[data-fiat-receive-amt]');
    const fiatTotalAmt = root.querySelector('[data-fiat-total-amt]');
    const fiatChips = root.querySelectorAll('[data-fiat-chip]');

    // Hidden form fields
    const formMethod = root.querySelector('[data-input-method]');
    const formCurrency = root.querySelector('[data-input-currency]');
    const formNetwork = root.querySelector('[data-input-network]');
    const formAmount = root.querySelector('[data-input-amount]');

    // Rates data
    let usdRates = {};
    try {
        usdRates = JSON.parse(root.dataset.usdRates || '{}');
    } catch (e) {
        usdRates = {};
    }

    let decimalsMap = {};
    try {
        decimalsMap = JSON.parse(root.dataset.decimals || '{}');
    } catch (e) {
        decimalsMap = {};
    }

    // 1. Tab Switching Function
    const switchTab = (tab) => {
        tabButtons.forEach(btn => {
            const isActive = btn.dataset.ocdep2Tab === tab;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        if (tab === 'crypto') {
            heroCrypto?.classList.add('is-active');
            heroFiat?.classList.remove('is-active');
            panelCrypto?.classList.add('is-active');
            panelFiat?.classList.remove('is-active');
            if (formMethod) formMethod.value = 'crypto';
            syncCryptoForm();
        } else {
            heroCrypto?.classList.remove('is-active');
            heroFiat?.classList.add('is-active');
            panelCrypto?.classList.remove('is-active');
            panelFiat?.classList.add('is-active');
            if (formMethod) formMethod.value = 'fiat';
            syncFiatForm();
        }
    };

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => switchTab(btn.dataset.ocdep2Tab));
    });

    // 2. Crypto Calculation
    const syncCryptoForm = () => {
        const usdVal = parseFloat(cryptoAmountInput?.value || 0);
        const selOption = cryptoCoinSelect?.selectedOptions[0];
        const coin = selOption ? selOption.value : 'USDT';
        const label = selOption ? selOption.dataset.name : 'Tether USD';
        const icon = selOption ? selOption.dataset.icon : '';
        const netIcon = selOption ? selOption.dataset.netIcon : '';
        const netLabel = selOption ? selOption.dataset.netLabel : 'TRC20 (Tron)';
        const netId = selOption ? selOption.dataset.netId : 'trc20';

        if (cryptoCoinCode) cryptoCoinCode.textContent = coin;
        if (cryptoCoinName) cryptoCoinName.textContent = label;
        if (cryptoCoinIcon && icon) cryptoCoinIcon.src = icon;
        if (cryptoNetIcon && netIcon) cryptoNetIcon.src = netIcon;
        if (cryptoNetTitle) cryptoNetTitle.textContent = netLabel;

        // Rate Calculation
        const coinRateUsd = parseFloat(usdRates[coin] || 1);
        let sendCoinAmt = usdVal;
        let rateRatioText = '1 USD ≈ 1.0000 ' + coin;

        if (coin === 'USDT' || coin === 'USDC') {
            sendCoinAmt = (usdVal * 1.0007);
            rateRatioText = '1 USD ≈ 1.0007 ' + coin;
        } else if (coinRateUsd > 0) {
            sendCoinAmt = usdVal / coinRateUsd;
            rateRatioText = '1 ' + coin + ' ≈ $' + coinRateUsd.toLocaleString();
        }

        const decimals = decimalsMap[coin] || (coin === 'BTC' || coin === 'ETH' ? 6 : 2);
        const formattedSend = sendCoinAmt.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: decimals
        });

        if (cryptoSendAmt) {
            cryptoSendAmt.textContent = formattedSend + ' ' + coin;
        }
        if (cryptoRateDisplay) {
            cryptoRateDisplay.textContent = rateRatioText;
        }

        // Quick chip active state
        cryptoChips.forEach(chip => {
            const chipVal = parseFloat(chip.dataset.cryptoChip);
            chip.classList.toggle('is-active', Math.abs(chipVal - usdVal) < 0.01);
        });

        // Set hidden form values
        if (formCurrency) formCurrency.value = coin;
        if (formNetwork) formNetwork.value = netId;
        if (formAmount) formAmount.value = usdVal.toFixed(2);
    };

    // Crypto Event Listeners
    cryptoAmountInput?.addEventListener('input', syncCryptoForm);
    cryptoCoinSelect?.addEventListener('change', syncCryptoForm);

    cryptoChips.forEach(chip => {
        chip.addEventListener('click', () => {
            if (cryptoAmountInput) {
                cryptoAmountInput.value = parseFloat(chip.dataset.cryptoChip).toFixed(2);
                syncCryptoForm();
            }
        });
    });

    // 3. Fiat Calculation & Payment Method Selection
    const syncFiatForm = () => {
        const rawVal = (fiatAmountInput?.value || '100000').replace(/[^0-9.]/g, '');
        const amount = parseFloat(rawVal) || 0;
        const formattedNgn = amount.toLocaleString();

        if (fiatReceiveAmt) {
            fiatReceiveAmt.textContent = formattedNgn + ' NGN';
        }
        if (fiatTotalAmt) {
            fiatTotalAmt.textContent = formattedNgn + ' NGN';
        }

        fiatChips.forEach(chip => {
            const chipVal = parseFloat(chip.dataset.fiatChip);
            chip.classList.toggle('is-active', Math.abs(chipVal - amount) < 0.01);
        });

        if (formCurrency) formCurrency.value = 'NGN';
        if (formAmount) formAmount.value = amount;
    };

    fiatAmountInput?.addEventListener('input', (e) => {
        const num = parseFloat(e.target.value.replace(/[^0-9.]/g, '')) || 0;
        syncFiatForm();
    });

    fiatChips.forEach(chip => {
        chip.addEventListener('click', () => {
            const val = parseFloat(chip.dataset.fiatChip);
            if (fiatAmountInput) {
                fiatAmountInput.value = val.toLocaleString();
                syncFiatForm();
            }
        });
    });

    fiatMethodCards.forEach(card => {
        card.addEventListener('click', () => {
            fiatMethodCards.forEach(c => c.classList.remove('is-active'));
            card.classList.add('is-active');
            if (formNetwork) formNetwork.value = card.dataset.fiatMethod;
        });
    });

    // Initial sync
    syncCryptoForm();
    syncFiatForm();
});

