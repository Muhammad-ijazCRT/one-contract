(() => {
    const root = document.querySelector('[data-oc-withdraw]');
    if (!root) return;

    const methods = [...root.querySelectorAll('[data-ocwd-method]')];
    const panels = [...root.querySelectorAll('[data-ocwd-panel]')];
    const amountInput = root.querySelector('[data-ocwd-amount]');
    const ngnInput = root.querySelector('[data-ocwd-ngn]');
    const balance = Number(root.dataset.balance || 0);
    const min = Number(root.dataset.min || 0);

    const setMethod = (method) => {
        methods.forEach((btn) => {
            const active = btn.dataset.ocwdMethod === method;
            btn.classList.toggle('is-active', active);
            btn.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach((panel) => {
            panel.classList.toggle('is-visible', panel.dataset.ocwdPanel === method);
        });
        const url = new URL(window.location.href);
        url.searchParams.set('method', method);
        window.history.replaceState({}, '', url);
    };

    methods.forEach((btn) => {
        btn.addEventListener('click', () => setMethod(btn.dataset.ocwdMethod));
    });

    root.querySelectorAll('[data-ocwd-chip]').forEach((chip) => {
        chip.addEventListener('click', () => {
            if (!amountInput || chip.disabled) return;
            amountInput.value = Number(chip.dataset.ocwdChip).toFixed(2);
            amountInput.dispatchEvent(new Event('input', { bubbles: true }));
            root.querySelectorAll('[data-ocwd-chip]').forEach((c) => c.classList.remove('is-active'));
            chip.classList.add('is-active');
        });
    });

    root.querySelector('[data-ocwd-max]')?.addEventListener('click', () => {
        if (!amountInput) return;
        amountInput.value = Math.max(min, balance).toFixed(8).replace(/\.?0+$/, '') || String(balance);
        amountInput.dispatchEvent(new Event('input', { bubbles: true }));
    });

    root.querySelectorAll('[data-ocwd-ngn-chip]').forEach((chip) => {
        chip.addEventListener('click', () => {
            if (!ngnInput || chip.disabled) return;
            ngnInput.value = Number(chip.dataset.ocwdNgnChip).toFixed(2);
            root.querySelectorAll('[data-ocwd-ngn-chip]').forEach((c) => c.classList.remove('is-active'));
            chip.classList.add('is-active');
        });
    });

    root.querySelector('[data-ocwd-ngn-max]')?.addEventListener('click', () => {
        if (!ngnInput) return;
        const max = Number(ngnInput.getAttribute('max') || 0);
        ngnInput.value = Math.max(0, max).toFixed(2);
    });

    const sendOtpBtn = root.querySelector('[data-ocwd-send-otp]');
    const otpStatus = root.querySelector('[data-ocwd-otp-status]');
    if (sendOtpBtn && otpStatus) {
        const showOtpStatus = (message, ok) => {
            otpStatus.textContent = message;
            otpStatus.classList.toggle('is-success', ok);
            otpStatus.classList.toggle('is-error', !ok);
            otpStatus.hidden = false;
        };

        sendOtpBtn.addEventListener('click', async () => {
            if (sendOtpBtn.disabled || sendOtpBtn.dataset.sending === '1') return;

            const token = document.querySelector('meta[name="csrf-token"]')?.content
                || root.querySelector('input[name="_token"]')?.value
                || '';
            const label = sendOtpBtn.textContent;

            sendOtpBtn.dataset.sending = '1';
            sendOtpBtn.disabled = true;
            sendOtpBtn.textContent = 'Sending...';
            otpStatus.hidden = true;

            try {
                const response = await fetch(sendOtpBtn.dataset.ocwdSendOtp, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: '{}',
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    showOtpStatus(
                        data.message || 'Could not send the verification email. Please try again in a moment.',
                        false,
                    );
                    return;
                }
                showOtpStatus(data.message || 'Verification code sent successfully.', true);
            } catch {
                showOtpStatus('Could not send the verification email. Please try again in a moment.', false);
            } finally {
                sendOtpBtn.dataset.sending = '0';
                sendOtpBtn.disabled = false;
                sendOtpBtn.textContent = label;
            }
        });
    }
})();
