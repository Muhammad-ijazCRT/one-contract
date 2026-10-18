document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-oc-convert]');
    if (!root) return;

    const form = root.querySelector('[data-occonv-form]');
    const fromPicker = root.querySelector('#occonv-from-picker');
    const toPicker = root.querySelector('#occonv-to-picker');
    const amountEl = root.querySelector('[data-occonv-amount]');
    const receiveEl = root.querySelector('[data-occonv-receive]');
    const fromAvail = root.querySelector('[data-occonv-from-avail]');
    const toAvail = root.querySelector('[data-occonv-to-avail]');
    const quoteBox = root.querySelector('[data-occonv-quote]');
    const rateEl = root.querySelector('[data-occonv-rate]');
    const feeEl = root.querySelector('[data-occonv-fee]');
    const feeNote = root.querySelector('[data-occonv-fee-note]');
    const receiveLabel = root.querySelector('[data-occonv-receive-label]');
    const submitBtn = root.querySelector('[data-occonv-submit]');
    const quoteUrl = root.dataset.quoteUrl;
    const ratesUrl = root.dataset.ratesUrl;
    const genericIcon = root.dataset.genericIcon || '/images/wallets/crypto-generic.svg';

    let balances = {};
    try {
        balances = JSON.parse(root.dataset.balances || '{}');
    } catch {
        balances = {};
    }

    const fmtBalance = (code, amount) => {
        const n = Number(amount || 0);
        const decimals = n >= 1 ? 2 : 6;
        return `${n.toLocaleString(undefined, { maximumFractionDigits: decimals })} ${code}`;
    };

    const getPickerInput = (picker) => picker?.querySelector('[data-occonv-coin-input]');
    const getPickerValue = (picker) => getPickerInput(picker)?.value || '';
    const getPickerDecimals = (picker) => {
        const code = getPickerValue(picker);
        const opt = picker?.querySelector(`[data-value="${code}"]`);
        return Number(opt?.dataset.decimals || 8);
    };

    const setPickerValue = (picker, code) => {
        if (!picker || !code) return false;
        const opt = picker.querySelector(`[data-value="${code}"]`);
        const input = getPickerInput(picker);
        if (!opt || !input) return false;

        input.value = code;
        const icon = picker.querySelector('[data-occonv-coin-icon]');
        const label = picker.querySelector('[data-occonv-coin-label]');
        if (icon) {
            icon.src = opt.dataset.icon || genericIcon;
            icon.onerror = () => { icon.src = genericIcon; };
        }
        if (label) label.textContent = code;

        picker.querySelectorAll('.occonv-coin-opt').forEach((btn) => {
            btn.classList.toggle('is-active', btn.dataset.value === code);
        });

        return true;
    };

    const closeAllPickers = (except = null) => {
        root.querySelectorAll('[data-occonv-coin-picker]').forEach((picker) => {
            if (picker === except) return;
            const menu = picker.querySelector('[data-occonv-coin-menu]');
            const toggle = picker.querySelector('[data-occonv-coin-toggle]');
            if (menu) menu.hidden = true;
            if (toggle) toggle.setAttribute('aria-expanded', 'false');
            picker.classList.remove('is-open');
        });
    };

    const initCoinPickers = () => {
        root.querySelectorAll('[data-occonv-coin-picker]').forEach((picker) => {
            const toggle = picker.querySelector('[data-occonv-coin-toggle]');
            const menu = picker.querySelector('[data-occonv-coin-menu]');

            toggle?.addEventListener('click', (event) => {
                event.stopPropagation();
                const willOpen = menu?.hidden;
                closeAllPickers();
                if (willOpen && menu) {
                    menu.hidden = false;
                    toggle.setAttribute('aria-expanded', 'true');
                    picker.classList.add('is-open');
                }
            });

            picker.querySelectorAll('.occonv-coin-opt').forEach((opt) => {
                opt.addEventListener('click', () => {
                    setPickerValue(picker, opt.dataset.value || '');
                    closeAllPickers();
                    syncAvailable();
                    refreshQuote();
                    picker.dispatchEvent(new CustomEvent('occonv:change', { bubbles: true }));
                });
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-occonv-coin-picker]')) {
                closeAllPickers();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeAllPickers();
        });
    };

    const syncAvailable = () => {
        const fromCode = getPickerValue(fromPicker);
        const toCode = getPickerValue(toPicker);
        if (fromAvail) fromAvail.textContent = fmtBalance(fromCode, balances[fromCode] ?? 0);
        if (toAvail) toAvail.textContent = fmtBalance(toCode, balances[toCode] ?? 0);
    };

    const refreshQuote = async () => {
        const amount = Number(amountEl?.value || 0);
        const fromCode = getPickerValue(fromPicker);
        const toCode = getPickerValue(toPicker);

        if (!amount || fromCode === toCode || !quoteUrl) {
            if (quoteBox) quoteBox.hidden = true;
            if (receiveEl) receiveEl.value = '0.00';
            return;
        }

        try {
            const res = await fetch(`${quoteUrl}?${new URLSearchParams({
                from_currency: fromCode,
                to_currency: toCode,
                amount: String(amount),
            })}`, { headers: { Accept: 'application/json' }, cache: 'no-store' });

            const data = await res.json();
            if (!res.ok) {
                if (quoteBox) quoteBox.hidden = true;
                return;
            }

            const toDecimals = getPickerDecimals(toPicker);
            const receive = Number(data.to_amount || 0);
            const receiveText = receive.toLocaleString(undefined, { maximumFractionDigits: toDecimals });

            if (receiveEl) receiveEl.value = receiveText;
            if (quoteBox) quoteBox.hidden = false;
            if (feeNote) feeNote.hidden = false;
            if (rateEl) rateEl.textContent = data.rate_display || `1 ${fromCode} = ${Number(data.rate).toFixed(8)} ${toCode}`;
            if (feeEl) feeEl.textContent = data.fee_display || `${data.fee_formatted || ''} (${Number(data.total_fee_percent || data.fee_percent || 0).toFixed(2)}%)`;
            if (receiveLabel) receiveLabel.textContent = `${receiveText} ${toCode}`;
        } catch {
            if (quoteBox) quoteBox.hidden = true;
        }
    };

    const setFrom = (code) => {
        if (setPickerValue(fromPicker, code)) {
            syncAvailable();
            refreshQuote();
        }
    };

    initCoinPickers();
    syncAvailable();
    refreshQuote();

    fromPicker?.addEventListener('occonv:change', () => {
        syncAvailable();
        refreshQuote();
    });

    toPicker?.addEventListener('occonv:change', () => {
        syncAvailable();
        refreshQuote();
    });

    amountEl?.addEventListener('input', refreshQuote);

    root.querySelector('[data-occonv-max]')?.addEventListener('click', () => {
        const bal = Number(balances[getPickerValue(fromPicker)] ?? 0);
        amountEl.value = bal > 0 ? String(bal) : '';
        refreshQuote();
    });

    root.querySelector('[data-occonv-swap]')?.addEventListener('click', () => {
        const from = getPickerValue(fromPicker);
        const to = getPickerValue(toPicker);
        setPickerValue(fromPicker, to);
        setPickerValue(toPicker, from);
        syncAvailable();
        refreshQuote();
    });

    root.querySelectorAll('[data-occonv-pick]').forEach((btn) => {
        btn.addEventListener('click', () => setFrom(btn.dataset.occonvPick || ''));
    });

    form?.addEventListener('submit', (event) => {
        const amount = Number(amountEl?.value || 0);
        const fromCode = getPickerValue(fromPicker);
        const toCode = getPickerValue(toPicker);
        if (!amount || fromCode === toCode) {
            event.preventDefault();
            return;
        }
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'PROCESSING…';
        }
    });

    if (ratesUrl) {
        setInterval(refreshQuote, Math.max(5000, Number(root.dataset.refreshSeconds || 5) * 1000));
    }
});
