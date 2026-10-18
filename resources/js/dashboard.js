document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initUserDropdown();
    initNotificationDropdown();
    initCountdown();
    initCopyReferral();
    initCopyWallet();
    initFullscreen();
    initPasswordToggle();
    initAdminNav();
    initAdminDateFilter();
    initConfirmDialogs();
    initProfileTabs();
    initSettingsSubtabs();
    initAuthMethodOptions();
    initWithdrawalFeePreview();
    initCopyModal();
    initDetailModal();
    initKycModal();
    initTwoFactorSetup();
    initHomePromoCarousel();
    initHomeEarningsChart();
    initHomeGamePicker();
    initHomeMoreSheet();
    initHomeConvertWidget();
    initTopBalanceToggle();
    initCurrencyPickerModal();
});

function initTopBalanceToggle() {
    const root = document.querySelector('[data-dash-top-balance]');
    if (!root) return;

    const btn = root.querySelector('[data-dash-balance-toggle]');
    const valueEl = root.querySelector('[data-dash-balance-value]');
    const icon = root.querySelector('[data-dash-balance-eye-icon]');
    if (!btn || !valueEl) return;

    const storageKey = 'oc_header_balance_hidden';
    let hidden = localStorage.getItem(storageKey) === '1';

    const apply = () => {
        root.classList.toggle('is-hidden', hidden);
        valueEl.textContent = hidden
            ? (valueEl.dataset.balanceHidden || '••••••')
            : (valueEl.dataset.balanceVisible || valueEl.textContent);
        if (icon) {
            icon.classList.toggle('fa-eye', !hidden);
            icon.classList.toggle('fa-eye-slash', hidden);
        }
        btn.setAttribute('aria-pressed', hidden ? 'true' : 'false');
    };

    apply();

    const toggle = (event) => {
        event.preventDefault();
        event.stopPropagation();
        hidden = !hidden;
        localStorage.setItem(storageKey, hidden ? '1' : '0');
        apply();
    };

    btn.addEventListener('click', toggle);
    btn.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            toggle(event);
        }
    });
}

function initCurrencyPickerModal() {
    const modal = document.querySelector('[data-oc-currency-picker]');
    if (!modal) return;

    const openers = document.querySelectorAll('[data-oc-currency-open]');
    const closers = modal.querySelectorAll('[data-oc-currency-close]');

    const open = () => {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ocscur-modal-open');
    };

    const close = () => {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ocscur-modal-open');
    };

    const applyHeaderBalance = (payload) => {
        const valueEl = document.querySelector('[data-dash-balance-value]');
        const codeEl = document.querySelector('[data-dash-balance-code]');
        const addBtn = document.querySelector('.dash-top-balance-add');

        if (valueEl && payload.formatted) {
            valueEl.textContent = payload.formatted;
            valueEl.setAttribute('data-balance-visible', payload.formatted);
        }

        if (codeEl && payload.currency) {
            codeEl.textContent = payload.currency;
        }

        if (addBtn && payload.deposit_url) {
            addBtn.setAttribute('href', payload.deposit_url);
        }

        modal.querySelectorAll('.ocscur-row').forEach((row) => {
            const code = row.closest('form')?.querySelector('input[name="currency"]')?.value;
            row.classList.toggle('is-selected', code === payload.currency);
        });
    };

    const formattedFromRow = (form) => {
        const code = form.querySelector('input[name="currency"]')?.value || '';
        const row = form.querySelector('.ocscur-row');
        const native = (row?.querySelector('.ocscur-bal b')?.textContent || '').trim();
        const usd = (row?.querySelector('.ocscur-bal small')?.textContent || '').trim();

        if (code === 'USDT' || code === 'USDC') {
            return native || '$0.00';
        }

        return usd || '$0.00';
    };

    openers.forEach((el) => {
        el.addEventListener('click', (event) => {
            if (event.target.closest('[data-dash-balance-toggle]')) {
                return;
            }
            event.preventDefault();
            open();
        });
    });

    closers.forEach((el) => {
        el.addEventListener('click', (event) => {
            event.preventDefault();
            close();
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            close();
        }
    });

    modal.addEventListener('submit', async (event) => {
        const form = event.target.closest('form.ocscur-form');
        if (!form || !modal.contains(form)) return;

        event.preventDefault();

        const currency = form.querySelector('input[name="currency"]')?.value || '';
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const optimistic = {
            currency,
            formatted: formattedFromRow(form),
            deposit_url: currency
                ? `/dashboard/deposit?currency=${encodeURIComponent(currency === 'NGN' ? 'USDT' : currency)}`
                : null,
        };

        applyHeaderBalance(optimistic);
        close();

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                body: new FormData(form),
                credentials: 'same-origin',
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data?.ok) {
                throw new Error(data?.message || data?.errors?.currency?.[0] || 'Could not update currency.');
            }

            applyHeaderBalance(data);
        } catch (error) {
            if (typeof showAppNotice === 'function') {
                showAppNotice({
                    title: 'Currency',
                    message: error?.message || 'Could not update currency.',
                    variant: 'error',
                });
            } else {
                window.alert(error?.message || 'Could not update currency.');
            }
        }
    });
}

function escapeNoticeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function showAppNotice({ title = 'Notice', message, variant = 'error', okText = 'OK' }) {
    return new Promise((resolve) => {
        document.querySelector('.auth-notice-backdrop')?.remove();

        const overlay = document.createElement('div');
        overlay.className = 'auth-notice-backdrop';

        const iconMarkup = variant === 'success'
            ? '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 7 10 17l-5-5" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
            : variant === 'error'
                ? '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v5m0 3h.01M10.29 3.86 2.82 17a2 2 0 0 0 1.71 3h14.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                : '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        overlay.innerHTML = `
            <div class="auth-notice auth-notice--${variant}" role="alertdialog" aria-modal="true" aria-labelledby="app-notice-title">
                <div class="auth-notice-icon">${iconMarkup}</div>
                <h3 class="auth-notice-title" id="app-notice-title">${escapeNoticeHtml(title)}</h3>
                <p class="auth-notice-copy">${escapeNoticeHtml(message)}</p>
                <button type="button" class="btn btn-gold auth-notice-ok">${escapeNoticeHtml(okText)}</button>
            </div>
        `;

        const close = () => {
            overlay.remove();
            document.body.classList.remove('auth-notice-open');
            document.removeEventListener('keydown', onKeyDown);
            resolve();
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') close();
        };

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) close();
        });

        overlay.querySelector('.auth-notice-ok')?.addEventListener('click', close);
        document.addEventListener('keydown', onKeyDown);
        document.body.classList.add('auth-notice-open');
        document.body.appendChild(overlay);
        overlay.querySelector('.auth-notice-ok')?.focus();
    });
}

function initDetailModal() {
    const triggers = document.querySelectorAll('[data-detail-modal]');
    if (!triggers.length) return;

    const overlay = document.createElement('div');
    overlay.className = 'confirm-modal-backdrop detail-modal-backdrop';
    overlay.setAttribute('hidden', '');
    overlay.innerHTML = `
        <div class="detail-modal-panel" role="dialog" aria-modal="true">
            <button type="button" class="modal-close-btn detail-modal-close" aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
            <div class="detail-modal-body" id="detail-modal-body">
                <div class="detail-modal-loading"><span class="detail-modal-spinner"></span> Loading details…</div>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    const body = overlay.querySelector('#detail-modal-body');
    const closeBtn = overlay.querySelector('.detail-modal-close');

    const close = () => {
        overlay.setAttribute('hidden', '');
        document.body.classList.remove('modal-open');
    };

    const open = async (url) => {
        body.innerHTML = '<div class="detail-modal-loading"><span class="detail-modal-spinner"></span> Loading details…</div>';
        overlay.removeAttribute('hidden');
        document.body.classList.add('modal-open');

        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error('Request failed');

            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const panel = doc.querySelector('.admin-detail-page') || doc.querySelector('.dash-page');

            if (panel) {
                body.innerHTML = '';
                body.appendChild(panel.cloneNode(true));
            } else {
                body.innerHTML = '<p class="detail-modal-error">Unable to load details.</p>';
            }
        } catch {
            body.innerHTML = '<p class="detail-modal-error">Failed to load details. Please try again.</p>';
        }
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const url = trigger.dataset.detailModal || trigger.getAttribute('href');
            if (url) open(url);
        });
    });

    closeBtn.addEventListener('click', close);

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !overlay.hasAttribute('hidden')) close();
    });
}

function initKycModal() {
    const modal = document.getElementById('kyc-modal');
    const triggers = document.querySelectorAll('[data-kyc-modal]');
    if (!modal || !triggers.length) return;

    const fill = (key) => modal.querySelector(`[data-kyc-fill="${key}"]`);

    const close = () => {
        modal.setAttribute('hidden', '');
        document.body.classList.remove('modal-open');
    };

    const open = (trigger) => {
        const name = trigger.dataset.kycName || '—';
        const member = trigger.dataset.kycMember || '';
        const email = trigger.dataset.kycEmail || '—';
        const status = trigger.dataset.kycStatus || 'pending';
        const statusLabel = trigger.dataset.kycStatusLabel || 'Under Verification';
        const face = trigger.dataset.kycFace || '';
        const selfie = trigger.dataset.kycSelfie || '';
        const idCard = trigger.dataset.kycId || '';
        const idBack = trigger.dataset.kycIdBack || '';
        const passport = trigger.dataset.kycPassport || '';
        const license = trigger.dataset.kycLicense || '';
        const licenseBack = trigger.dataset.kycLicenseBack || '';
        const note = trigger.dataset.kycNote || '';
        const approveUrl = trigger.dataset.kycApprove || '';
        const rejectUrl = trigger.dataset.kycReject || '';
        const canApprove = trigger.dataset.kycCanApprove === '1';

        fill('name').textContent = name;
        fill('member').textContent = member || '—';
        fill('email').textContent = email;

        const badge = fill('status-badge');
        badge.textContent = statusLabel;
        badge.className = 'status-badge ' + (
            status === 'approved' ? 'success' : status === 'rejected' ? 'danger' : 'pending'
        );

        const faceImg = fill('face');
        const faceEmpty = fill('face-empty');
        const faceBroken = fill('face-broken');
        const faceLink = fill('face-link');
        const bindImg = (img, emptyEl, brokenEl, link, url) => {
            if (!img) return;
            img.onerror = null;
            if (url) {
                img.onload = () => {
                    img.hidden = false;
                    if (emptyEl) emptyEl.hidden = true;
                    if (brokenEl) brokenEl.hidden = true;
                };
                img.onerror = () => {
                    img.hidden = true;
                    img.removeAttribute('src');
                    if (emptyEl) emptyEl.hidden = true;
                    if (brokenEl) brokenEl.hidden = false;
                    if (link) {
                        link.removeAttribute('href');
                        link.style.pointerEvents = 'none';
                    }
                };
                img.src = url;
                img.hidden = false;
                if (emptyEl) emptyEl.hidden = true;
                if (brokenEl) brokenEl.hidden = true;
                if (link) {
                    link.href = url;
                    link.style.pointerEvents = 'auto';
                }
            } else {
                img.removeAttribute('src');
                img.hidden = true;
                if (emptyEl) emptyEl.hidden = false;
                if (brokenEl) brokenEl.hidden = true;
                if (link) {
                    link.removeAttribute('href');
                    link.style.pointerEvents = 'none';
                }
            }
        };

        bindImg(faceImg, faceEmpty, faceBroken, faceLink, face);

        const selfieImg = fill('selfie');
        const selfieEmpty = fill('selfie-empty');
        const selfieBroken = fill('selfie-broken');
        const selfieLink = fill('selfie-link');
        bindImg(selfieImg, selfieEmpty, selfieBroken, selfieLink, selfie);

        const bindSlot = (key, url) => {
            bindImg(fill(key), fill(`${key}-empty`), fill(`${key}-broken`), fill(`${key}-link`), url);
        };
        bindSlot('id', idCard);
        bindSlot('id-back', idBack);
        bindSlot('passport', passport);
        bindSlot('license', license);
        bindSlot('license-back', licenseBack);

        const noteWrap = fill('note-wrap');
        if (note) {
            fill('note').textContent = note;
            noteWrap.hidden = false;
        } else {
            noteWrap.hidden = true;
            fill('note').textContent = '';
        }

        const docsMissing = fill('docs-missing');
        if (docsMissing) {
            docsMissing.hidden = canApprove || status === 'approved';
        }

        fill('approve-form').action = approveUrl;
        fill('reject-form').action = rejectUrl;
        const rejectNoteInput = fill('note-input');
        rejectNoteInput.value = '';
        rejectNoteInput.required = true;

        const approveBtn = fill('approve-btn');
        approveBtn.disabled = status === 'approved' || !canApprove;
        approveBtn.title = !canApprove
            ? 'ID, selfie, and live verification images are required'
            : (status === 'approved' ? 'Already approved' : 'Approve KYC');
        fill('reject-btn').disabled = status === 'rejected';

        modal.removeAttribute('hidden');
        document.body.classList.add('modal-open');
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            open(trigger);
        });
    });

    modal.querySelectorAll('[data-kyc-close]').forEach((btn) => {
        btn.addEventListener('click', close);
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hasAttribute('hidden')) close();
    });
}

function initCopyModal() {
    const triggers = document.querySelectorAll('[data-copy-modal]');
    if (!triggers.length) return;

    const overlay = document.createElement('div');
    overlay.className = 'confirm-modal-backdrop copy-modal-backdrop';
    overlay.setAttribute('hidden', '');
    overlay.innerHTML = `
        <div class="confirm-modal copy-modal" role="dialog" aria-modal="true" aria-labelledby="copy-modal-title">
            <div class="confirm-modal-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            </div>
            <h3 class="confirm-modal-title" id="copy-modal-title">Wallet Address</h3>
            <div class="copy-modal-value" id="copy-modal-value"></div>
            <div class="confirm-modal-actions">
                <button type="button" class="btn btn-outline-dark copy-modal-close">Close</button>
                <button type="button" class="btn btn-primary copy-modal-copy">Copy Address</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    const titleEl = overlay.querySelector('#copy-modal-title');
    const valueEl = overlay.querySelector('#copy-modal-value');
    const closeBtn = overlay.querySelector('.copy-modal-close');
    const copyBtn = overlay.querySelector('.copy-modal-copy');
    let currentValue = '';

    const close = () => {
        overlay.setAttribute('hidden', '');
        document.body.classList.remove('modal-open');
    };

    const open = (trigger) => {
        currentValue = trigger.dataset.copyModal || '';
        titleEl.textContent = trigger.dataset.copyModalLabel || 'Wallet Address';
        valueEl.textContent = currentValue;
        copyBtn.textContent = 'Copy Address';
        copyBtn.classList.remove('copy-modal-copy--done');
        overlay.removeAttribute('hidden');
        document.body.classList.add('modal-open');
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            open(trigger);
        });
    });

    copyBtn.addEventListener('click', async () => {
        if (!currentValue) return;

        try {
            await navigator.clipboard.writeText(currentValue);
        } catch {
            const range = document.createRange();
            range.selectNodeContents(valueEl);
            const selection = window.getSelection();
            selection?.removeAllRanges();
            selection?.addRange(range);
            document.execCommand('copy');
            selection?.removeAllRanges();
        }

        copyBtn.textContent = 'Copied!';
        copyBtn.classList.add('copy-modal-copy--done');
        setTimeout(() => {
            copyBtn.textContent = 'Copy Address';
            copyBtn.classList.remove('copy-modal-copy--done');
        }, 1800);
    });

    closeBtn.addEventListener('click', close);

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !overlay.hasAttribute('hidden')) close();
    });
}

function initSettingsSubtabs() {
    const tabs = document.querySelectorAll('.settings-subtabs .settings-subtab[data-tab-target]');
    if (!tabs.length) return;

    const panes = document.querySelectorAll('.settings-subtab-content .tab-pane[data-tab-pane]');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tabTarget;

            tabs.forEach((t) => {
                const isActive = t === tab;
                t.classList.toggle('active', isActive);
                t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            panes.forEach((pane) => {
                pane.classList.toggle('active', pane.dataset.tabPane === target);
            });
        });
    });
}

function initProfileTabs() {
    const tabs = document.querySelectorAll('.profile-tabs .nav-link[data-tab-target]');
    if (!tabs.length) return;

    const panes = document.querySelectorAll('.profile-tab-content .tab-pane[data-tab-pane]');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.tabTarget;

            tabs.forEach((t) => {
                const isActive = t === tab;
                t.classList.toggle('active', isActive);
                t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            panes.forEach((p) => {
                p.classList.toggle('active', p.dataset.tabPane === target);
            });

            const url = new URL(window.location.href);
            url.searchParams.set('tab', target);
            window.history.replaceState({}, '', url);
        });
    });
}

function initAuthMethodOptions() {
    const cards = document.querySelectorAll('.auth-method-card[data-auth-method]');
    const panels = document.querySelectorAll('.auth-method-panel[data-auth-panel]');
    if (!cards.length || !panels.length) return;

    const activate = (method) => {
        cards.forEach((card) => {
            const isActive = card.dataset.authMethod === method;
            card.classList.toggle('is-active', isActive);
            card.setAttribute('aria-checked', isActive ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            panel.classList.toggle('is-active', panel.dataset.authPanel === method);
        });
    };

    cards.forEach((card) => {
        card.addEventListener('click', () => activate(card.dataset.authMethod));
    });
}

function initWithdrawalFeePreview() {
    const amountInput = document.getElementById('reward_amount');
    const preview = document.getElementById('reward-fee-preview');
    const grossEl = document.getElementById('reward-fee-gross');
    const feeEl = document.getElementById('reward-fee-value');
    const netEl = document.getElementById('reward-fee-net');
    const netInput = document.getElementById('reward_net');

    if (!amountInput || !preview || !grossEl || !feeEl || !netEl) return;

    const rate = parseFloat(amountInput.dataset.withdrawalFeeRate || '0');

    const formatAmount = (value) => `${value.toFixed(2)} USDT`;

    const update = () => {
        const amount = parseFloat(amountInput.value);

        if (!Number.isFinite(amount) || amount <= 0) {
            preview.hidden = true;
            if (netInput) netInput.value = '';
            return;
        }

        const fee = Math.round(amount * rate * 1e8) / 1e8;
        const net = Math.max(0, Math.round((amount - fee) * 1e8) / 1e8);

        grossEl.textContent = formatAmount(amount);
        feeEl.textContent = formatAmount(fee);
        netEl.textContent = formatAmount(net);
        if (netInput) netInput.value = net.toFixed(2);
        preview.hidden = false;
    };

    amountInput.addEventListener('input', update);
    amountInput.addEventListener('change', update);
    update();
}

function initSidebar() {
    const btn = document.getElementById('dash-menu-btn');
    const sidebar = document.getElementById('dash-sidebar');
    const overlay = document.getElementById('dash-overlay');
    const layout = document.getElementById('dash-layout');

    if (!btn || !sidebar || !layout) return;

    const isMobile = () => window.innerWidth < 1024;

    const updateOverlay = () => {
        const collapsed = layout.classList.contains('sidebar-collapsed');

        if (isMobile() && !collapsed) {
            overlay?.classList.add('show');
            document.body.classList.add('sidebar-open');
        } else {
            overlay?.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    };

    const setInitialState = () => {
        if (!isMobile()) {
            layout.classList.remove('sidebar-collapsed');
        } else {
            layout.classList.add('sidebar-collapsed');
        }
        updateOverlay();
    };

    const close = () => {
        layout.classList.add('sidebar-collapsed');
        updateOverlay();
    };

    const open = () => {
        layout.classList.remove('sidebar-collapsed');
        updateOverlay();
    };

    const toggle = () => {
        layout.classList.toggle('sidebar-collapsed');
        updateOverlay();
    };

    setInitialState();

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggle();
    });

    overlay?.addEventListener('click', close);

    sidebar.querySelectorAll('.dash-nav-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (isMobile()) close();
        });
    });

    window.addEventListener('resize', () => {
        if (!isMobile()) {
            layout.classList.remove('sidebar-collapsed');
            overlay?.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        } else if (!layout.classList.contains('sidebar-collapsed')) {
            updateOverlay();
        } else {
            overlay?.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    });
}

function initUserDropdown() {
    const dropdown = document.getElementById('user-dropdown');
    const toggle = document.getElementById('user-dropdown-toggle');
    const notificationDropdown = document.getElementById('notification-dropdown');

    if (!dropdown || !toggle) return;

    const close = () => {
        dropdown.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(isOpen));

        if (isOpen) {
            notificationDropdown?.classList.remove('open');
            document.getElementById('notification-dropdown-toggle')?.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
}

function initNotificationDropdown() {
    const dropdown = document.getElementById('notification-dropdown');
    const toggle = document.getElementById('notification-dropdown-toggle');
    const userDropdown = document.getElementById('user-dropdown');
    const userToggle = document.getElementById('user-dropdown-toggle');

    if (!dropdown || !toggle) return;

    const close = () => {
        dropdown.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
    };

    const clearNotificationCounter = () => {
        document.getElementById('notification-badge')?.remove();
        document.getElementById('notification-header-count')?.remove();
        dropdown.querySelectorAll('.unread').forEach((item) => item.classList.remove('unread'));
    };

    const markNotificationsRead = () => {
        const url = dropdown.dataset.markReadUrl;
        if (!url || dropdown.dataset.marked === '1') return;

        dropdown.dataset.marked = '1';
        clearNotificationCounter();

        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ filter: 'all' }),
        }).catch(() => {
            dropdown.dataset.marked = '0';
        });
    };

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle('open');
        toggle.setAttribute('aria-expanded', String(isOpen));

        if (isOpen) {
            userDropdown?.classList.remove('open');
            userToggle?.setAttribute('aria-expanded', 'false');
            markNotificationsRead();
        }
    });

    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target)) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
}

function initCountdown() {
    const formatParts = (diff) => {
        const days = Math.floor(diff / 86400000);
        const hours = Math.floor((diff % 86400000) / 3600000);
        const minutes = Math.floor((diff % 3600000) / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);

        return { days, hours, minutes, seconds };
    };

    const pad = (val) => String(val).padStart(2, '0');

    document.querySelectorAll('.js-countdown').forEach((el) => {
        const target = new Date(el.dataset.target).getTime();
        if (Number.isNaN(target)) return;

        const tick = () => {
            const parts = formatParts(Math.max(0, target - Date.now()));
            if (el.dataset.format === 'long') {
                el.textContent = `${parts.days}d ${pad(parts.hours)}h ${pad(parts.minutes)}m ${pad(parts.seconds)}s`;
            } else if (el.dataset.format === 'short') {
                el.textContent = `${parts.days}d ${pad(parts.hours)}h ${pad(parts.minutes)}m`;
            } else {
                el.textContent = `${pad(parts.days)}d ${pad(parts.hours)}:${pad(parts.minutes)}:${pad(parts.seconds)}`;
            }
        };

        tick();
        setInterval(tick, 1000);
    });

    document.querySelectorAll('.js-countdown-blocks').forEach((el) => {
        const target = new Date(el.dataset.target).getTime();
        if (Number.isNaN(target)) return;

        const tick = () => {
            const parts = formatParts(Math.max(0, target - Date.now()));
            el.querySelectorAll('[data-cd]').forEach((node) => {
                const key = node.getAttribute('data-cd');
                if (key && Object.prototype.hasOwnProperty.call(parts, key)) {
                    node.textContent = pad(parts[key]);
                }
            });
        };

        tick();
        setInterval(tick, 1000);
    });

    // Legacy single withdrawal countdown (older pages)
    const el = document.getElementById('withdrawal-countdown');
    if (!el) return;

    const target = new Date(el.dataset.target).getTime();

    const tick = () => {
        const parts = formatParts(Math.max(0, target - Date.now()));
        const set = (id, val) => {
            const node = document.getElementById(id);
            if (node) node.textContent = pad(val);
        };

        set('cd-days', parts.days);
        set('cd-hours', parts.hours);
        set('cd-minutes', parts.minutes);
        set('cd-seconds', parts.seconds);
    };

    tick();
    setInterval(tick, 1000);
}

function initCopyReferral() {
    const input = document.getElementById('referral-link-input');
    const copyBtn = document.getElementById('copy-referral-btn');
    const sidebarBtn = document.getElementById('copy-referral-sidebar');

    const getLink = () => {
        return input?.value
            || window.referralLink
            || sidebarBtn?.dataset.referralLink
            || '';
    };

    const showCopied = (btn) => {
        if (!btn) return;
        if (btn.dataset.originalHtml === undefined) {
            btn.dataset.originalHtml = btn.innerHTML;
        }
        clearTimeout(btn._copyResetTimer);
        btn.textContent = 'Copied!';
        btn.classList.add('copied');
        btn._copyResetTimer = setTimeout(() => {
            btn.innerHTML = btn.dataset.originalHtml;
            btn.classList.remove('copied');
        }, 2000);
    };

    const copy = async (triggerBtn) => {
        const text = getLink();
        if (!text) return;

        try {
            await navigator.clipboard.writeText(text);
            if (copyBtn) copyBtn.classList.add('copied');
            showCopied(triggerBtn);
            setTimeout(() => copyBtn?.classList.remove('copied'), 2000);
        } catch {
            if (input) {
                input.select();
                document.execCommand('copy');
                showCopied(triggerBtn);
            }
        }
    };

    copyBtn?.addEventListener('click', () => copy(copyBtn));
    sidebarBtn?.addEventListener('click', () => copy(sidebarBtn));
}

function initCopyWallet() {
    const wallet = window.platformWallet || '';
    const btn = document.getElementById('copy-wallet-btn');
    const code = document.getElementById('platform-wallet');

    if (!btn || !wallet) return;

    btn.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(wallet);
            btn.classList.add('copied');
            setTimeout(() => btn.classList.remove('copied'), 2000);
        } catch {
            if (code) {
                const range = document.createRange();
                range.selectNodeContents(code);
                window.getSelection()?.removeAllRanges();
                window.getSelection()?.addRange(range);
                document.execCommand('copy');
            }
        }
    });
}

function initFullscreen() {
    const btn = document.getElementById('fullscreen-btn');
    if (!btn) return;

    btn.addEventListener('click', () => {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen?.();
        } else {
            document.exitFullscreen?.();
        }
    });
}

function initPasswordToggle() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const field = button.closest('.password-field');
        const input = field?.querySelector('input');

        if (!input) return;

        button.addEventListener('click', () => {
            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.classList.toggle('is-visible', !isVisible);
            button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        });
    });
}

function initAdminNav() {
    document.querySelectorAll('[data-nav-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            toggle.closest('.admin-nav-group')?.classList.toggle('open');
        });
    });
}

function initAdminDateFilter() {
    const form = document.getElementById('admin-date-form');
    const input = document.getElementById('admin-date-input');

    if (!form || !input) return;

    input.addEventListener('change', () => {
        if (input.value) {
            form.submit();
        }
    });
}

function initConfirmDialogs() {
    const overlay = document.createElement('div');
    overlay.className = 'confirm-modal-backdrop';
    overlay.setAttribute('hidden', '');
    overlay.innerHTML = `
        <div class="confirm-modal" role="alertdialog" aria-modal="true" aria-labelledby="confirm-modal-title">
            <div class="confirm-modal-icon-wrap">
                <div class="confirm-modal-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="m8.5 12.2 2.4 2.4 4.6-4.8"/>
                    </svg>
                </div>
            </div>
            <h3 class="confirm-modal-title" id="confirm-modal-title">Please Confirm</h3>
            <p class="confirm-modal-copy">Are you sure?</p>
            <textarea class="confirm-modal-textarea" hidden></textarea>
            <div class="confirm-modal-actions">
                <button type="button" class="btn confirm-modal-cancel">Cancel</button>
                <button type="button" class="btn btn-success confirm-modal-ok">Yes, Continue</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    const modal = overlay.querySelector('.confirm-modal');
    const iconWrap = overlay.querySelector('.confirm-modal-icon');
    const titleEl = overlay.querySelector('.confirm-modal-title');
    const copyEl = overlay.querySelector('.confirm-modal-copy');
    const textareaEl = overlay.querySelector('.confirm-modal-textarea');
    const okBtn = overlay.querySelector('.confirm-modal-ok');
    const cancelBtn = overlay.querySelector('.confirm-modal-cancel');

    const dangerIcon = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3.2 2.8 19.2A1.4 1.4 0 0 0 4 21.2h16a1.4 1.4 0 0 0 1.2-2L12 3.2Z"/>
            <path d="M12 9.2v5"/>
            <circle cx="12" cy="17.1" r="1.15" fill="currentColor" stroke="none"/>
        </svg>
    `;
    const defaultIcon = iconWrap.innerHTML;

    let pendingForm = null;

    const close = () => {
        overlay.setAttribute('hidden', '');
        document.body.classList.remove('modal-open');
        pendingForm = null;
        textareaEl.value = '';
        textareaEl.hidden = true;
    };

    const resolveTemplate = (form, template) => {
        return template.replace(/\{(\w+)\}/g, (match, fieldName) => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (!field || !field.value) return match;
            if (field.tagName === 'SELECT') {
                const opt = field.options[field.selectedIndex];
                return opt ? opt.textContent.trim() : field.value;
            }
            return field.value;
        });
    };

    const open = (form) => {
        pendingForm = form;
        titleEl.textContent = form.dataset.confirmTitle || 'Please Confirm';
        copyEl.textContent = resolveTemplate(form, form.dataset.confirm || 'Are you sure?');
        okBtn.textContent = form.dataset.confirmOk || 'Yes, Continue';

        const isDanger = form.dataset.confirmVariant === 'danger';
        modal.classList.toggle('confirm-modal--danger', isDanger);
        iconWrap.innerHTML = isDanger ? dangerIcon : defaultIcon;
        okBtn.classList.toggle('btn-success', !isDanger);
        okBtn.classList.toggle('btn-danger', isDanger);
        okBtn.classList.remove('btn-primary');

        const notesField = form.querySelector('[name="notes"]');
        if (form.dataset.confirmTextarea) {
            textareaEl.hidden = false;
            textareaEl.placeholder = form.dataset.confirmTextarea;
            textareaEl.value = notesField?.value || '';
        } else {
            textareaEl.hidden = true;
        }

        overlay.removeAttribute('hidden');
        document.body.classList.add('modal-open');

        if (!textareaEl.hidden) {
            setTimeout(() => textareaEl.focus(), 50);
        }
    };

    document.addEventListener('submit', (e) => {
        const form = e.target.closest ? e.target.closest('form[data-confirm]') : null;
        if (!form) return;

        e.preventDefault();
        if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
            return;
        }
        open(form);
    });

    okBtn.addEventListener('click', () => {
        const form = pendingForm;
        if (!form) return;

        if (!textareaEl.hidden) {
            let notesField = form.querySelector('[name="notes"]');
            if (!notesField) {
                notesField = document.createElement('input');
                notesField.type = 'hidden';
                notesField.name = 'notes';
                form.appendChild(notesField);
            }
            notesField.value = textareaEl.value;
        }

        close();
        form.submit();
    });

    cancelBtn.addEventListener('click', close);

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) close();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !overlay.hasAttribute('hidden')) close();
    });
}

function initTwoFactorSetup() {
    const section = document.querySelector('[data-two-factor-setup]');
    const modal = document.getElementById('two-factor-setup-modal');

    if (!section || !modal) return;

    const qrContainer = document.getElementById('two-factor-qr-container');
    const manualKeyEl = document.getElementById('two-factor-manual-key');
    const manualKeyWrap = document.getElementById('two-factor-manual-key-wrap');
    const generateBtn = document.getElementById('two-factor-generate-btn');
    const openBtn = document.getElementById('two-factor-setup-open');
    const startNote = document.getElementById('two-factor-start-note');
    const closeBtn = modal.querySelector('.two-factor-modal-close');
    const codeInput = document.getElementById('two_factor_confirm_code');
    const setupUrl = section.dataset.setupUrl;
    const csrfToken = section.dataset.csrfToken;

    const openModal = () => {
        modal.removeAttribute('hidden');
        document.body.classList.add('modal-open');
        window.setTimeout(() => codeInput?.focus(), 50);
    };

    const closeModal = () => {
        modal.setAttribute('hidden', '');
        document.body.classList.remove('modal-open');
    };

    const showReadyState = () => {
        generateBtn?.setAttribute('hidden', '');
        openBtn?.removeAttribute('hidden');

        if (startNote) {
            startNote.textContent = 'Scan the QR code with your authenticator app, then enter the code to connect.';
        }
    };

    const populateSetup = (qrSvg, secret) => {
        if (qrContainer) {
            qrContainer.innerHTML = qrSvg;
        }

        if (manualKeyEl) {
            manualKeyEl.textContent = secret;
        }

        manualKeyWrap?.removeAttribute('hidden');
        showReadyState();
    };

    generateBtn?.addEventListener('click', async () => {
        if (!setupUrl || !csrfToken) return;

        generateBtn.disabled = true;
        const originalLabel = generateBtn.innerHTML;
        generateBtn.innerHTML = 'Generating…';

        try {
            const response = await fetch(setupUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Unable to generate QR code.');
            }

            populateSetup(data.qr_svg, data.secret);
            openModal();
        } catch (error) {
            showAppNotice({
                title: 'Unable to Generate QR',
                message: error.message || 'Unable to generate QR code. Please try again.',
                variant: 'error',
            });
        } finally {
            generateBtn.disabled = false;
            generateBtn.innerHTML = originalLabel;
        }
    });

    openBtn?.addEventListener('click', openModal);
    closeBtn?.addEventListener('click', closeModal);

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hasAttribute('hidden')) {
            closeModal();
        }
    });

    if (section.dataset.openOnLoad === 'true') {
        showReadyState();
        openModal();
    }
}

function initHomePromoCarousel() {
    const root = document.querySelector('[data-och-promo]');
    if (!root) return;

    const slides = Array.from(root.querySelectorAll('[data-och-slide]'));
    const dots = Array.from(root.querySelectorAll('[data-och-dot]'));
    if (slides.length < 2) return;

    let index = 0;
    let timer = null;

    const hydrateSlide = (slide) => {
        if (!slide) return;
        slide.querySelectorAll('source[data-srcset]').forEach((source) => {
            const srcset = source.getAttribute('data-srcset');
            if (!srcset) return;
            source.setAttribute('srcset', srcset);
            source.removeAttribute('data-srcset');
        });
        const img = slide.querySelector('img[data-src]');
        if (!img) return;
        const src = img.getAttribute('data-src');
        if (!src) return;
        img.src = src;
        img.removeAttribute('data-src');
    };

    const show = (next) => {
        index = (next + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle('is-active', i === index));
        dots.forEach((dot, i) => dot.classList.toggle('is-active', i === index));
        hydrateSlide(slides[index]);
        // Warm the following slide so the next rotation feels instant
        hydrateSlide(slides[(index + 1) % slides.length]);
    };

    const start = () => {
        stop();
        if (window.matchMedia('(min-width: 1100px)').matches) {
            return;
        }
        timer = window.setInterval(() => show(index + 1), 5500);
    };

    const stop = () => {
        if (timer) window.clearInterval(timer);
        timer = null;
    };

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            const i = Number(dot.dataset.ochDot || 0);
            show(i);
            start();
        });
    });

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    hydrateSlide(slides[0]);
    hydrateSlide(slides[1]);
    start();
}

function initHomeEarningsChart() {
    const home = document.querySelector('.oc-home[data-earnings-charts], .oc-home[data-earnings-chart]');
    const canvas = document.getElementById('och-earnings-chart');
    if (!home || !canvas) return;

    let charts = {};
    try {
        charts = JSON.parse(home.getAttribute('data-earnings-charts') || '{}');
    } catch (e) {
        charts = {};
    }

    if (!charts.month && !charts.week) {
        try {
            const single = JSON.parse(home.getAttribute('data-earnings-chart') || '{}');
            charts = { month: single, week: single };
        } catch (e) {
            charts = {};
        }
    }

    const rangeRoot = home.querySelector('[data-och-range]');
    const rangeBtn = rangeRoot?.querySelector('[data-och-range-btn]');
    const rangeMenu = rangeRoot?.querySelector('.och-chart-range-menu');
    const rangeLabel = rangeRoot?.querySelector('[data-och-range-label]');
    const totalEl = home.querySelector('[data-och-chart-total]');
    const changeEl = home.querySelector('[data-och-chart-change]');
    const labelsMap = { week: 'This Week', month: 'This Month' };

    let activeRange = charts.month ? 'month' : 'week';
    let labels = [];
    let values = [];

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const applyRange = (range) => {
        const payload = charts[range] || charts.month || charts.week || { labels: [], values: [], total: 0, change_pct: 0 };
        activeRange = range;
        labels = Array.isArray(payload.labels) ? payload.labels : [];
        values = Array.isArray(payload.values) ? payload.values.map(Number) : [];

        if (rangeLabel) rangeLabel.textContent = labelsMap[range] || 'This Month';
        if (totalEl) totalEl.textContent = `$${Number(payload.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (changeEl) {
            const pct = Number(payload.change_pct || 0);
            changeEl.textContent = `${pct >= 0 ? '+' : ''}${pct.toFixed(1)}% from last period`;
            changeEl.classList.toggle('is-up', pct >= 0);
            changeEl.classList.toggle('is-down', pct < 0);
        }

        rangeRoot?.querySelectorAll('[data-och-range-option]').forEach((btn) => {
            btn.classList.toggle('is-active', btn.getAttribute('data-och-range-option') === range);
        });

        draw();
    };

    const closeRange = () => {
        rangeRoot?.classList.remove('is-open');
        if (rangeMenu) rangeMenu.hidden = true;
        if (rangeBtn) rangeBtn.setAttribute('aria-expanded', 'false');
    };

    const openRange = () => {
        rangeRoot?.classList.add('is-open');
        if (rangeMenu) rangeMenu.hidden = false;
        if (rangeBtn) rangeBtn.setAttribute('aria-expanded', 'true');
    };

    rangeBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (rangeRoot?.classList.contains('is-open')) closeRange();
        else openRange();
    });

    rangeRoot?.querySelectorAll('[data-och-range-option]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            applyRange(btn.getAttribute('data-och-range-option') || 'month');
            closeRange();
        });
    });

    document.addEventListener('click', (e) => {
        if (!rangeRoot?.contains(e.target)) closeRange();
    });

    const draw = () => {
        if (!values.length) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            return;
        }

        const dpr = window.devicePixelRatio || 1;
        const width = canvas.clientWidth || 320;
        const height = canvas.clientHeight || 180;
        canvas.width = Math.floor(width * dpr);
        canvas.height = Math.floor(height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, width, height);

        const pad = { t: 16, r: 12, b: 28, l: 12 };
        const plotW = width - pad.l - pad.r;
        const plotH = height - pad.t - pad.b;
        const max = Math.max(...values, 1);
        const min = 0;
        const span = Math.max(max - min, 1);

        const points = values.map((v, i) => {
            const x = pad.l + (values.length === 1 ? plotW / 2 : (i / (values.length - 1)) * plotW);
            const y = pad.t + plotH - ((v - min) / span) * plotH;
            return { x, y, v };
        });

        ctx.strokeStyle = 'rgba(148, 163, 184, 0.12)';
        ctx.lineWidth = 1;
        for (let i = 0; i < 4; i += 1) {
            const y = pad.t + (plotH / 3) * i;
            ctx.beginPath();
            ctx.moveTo(pad.l, y);
            ctx.lineTo(width - pad.r, y);
            ctx.stroke();
        }

        const grad = ctx.createLinearGradient(0, pad.t, 0, pad.t + plotH);
        grad.addColorStop(0, 'rgba(255, 214, 0, 0.35)');
        grad.addColorStop(1, 'rgba(255, 214, 0, 0)');
        ctx.beginPath();
        points.forEach((p, i) => {
            if (i === 0) ctx.moveTo(p.x, p.y);
            else ctx.lineTo(p.x, p.y);
        });
        ctx.lineTo(points[points.length - 1].x, pad.t + plotH);
        ctx.lineTo(points[0].x, pad.t + plotH);
        ctx.closePath();
        ctx.fillStyle = grad;
        ctx.fill();

        ctx.beginPath();
        points.forEach((p, i) => {
            if (i === 0) ctx.moveTo(p.x, p.y);
            else ctx.lineTo(p.x, p.y);
        });
        ctx.strokeStyle = '#ffd600';
        ctx.lineWidth = 2.4;
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.shadowColor = 'rgba(255, 214, 0, 0.55)';
        ctx.shadowBlur = 10;
        ctx.stroke();
        ctx.shadowBlur = 0;

        points.forEach((p, i) => {
            ctx.beginPath();
            ctx.arc(p.x, p.y, 3.2, 0, Math.PI * 2);
            ctx.fillStyle = '#111';
            ctx.fill();
            ctx.lineWidth = 2;
            ctx.strokeStyle = '#ffd600';
            ctx.stroke();

            const label = labels[i] || '';
            if (!label) return;
            ctx.fillStyle = '#94a3b8';
            ctx.font = '600 11px Poppins, sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText(label, p.x, height - 8);
        });
    };

    applyRange(activeRange);
    window.addEventListener('resize', draw);
}


function initHomeGamePicker() {
    const triggers = document.querySelectorAll('[data-game-picker]');
    const modal = document.getElementById('och-game-modal');
    if (!triggers.length || !modal) return;

    const open = () => {
        modal.hidden = false;
        document.body.classList.add('modal-open');
    };

    const close = () => {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
    };

    triggers.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            open();
        });
    });

    modal.querySelectorAll('[data-game-picker-close]').forEach((el) => {
        el.addEventListener('click', close);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.hidden) close();
    });
}

function initHomeMoreSheet() {
    const sheet = document.getElementById('och4-more-sheet');
    const openBtn = document.querySelector('[data-och4-more]');
    if (!sheet || !openBtn) return;

    const open = () => {
        sheet.hidden = false;
        document.body.classList.add('modal-open');
    };
    const close = () => {
        sheet.hidden = true;
        document.body.classList.remove('modal-open');
    };

    openBtn.addEventListener('click', open);
    sheet.querySelectorAll('[data-och4-more-close]').forEach((el) => {
        el.addEventListener('click', close);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !sheet.hidden) close();
    });
}

function initHomeConvertWidget() {
    const root = document.querySelector('[data-och4-convert]');
    const home = document.querySelector('.oc-home--v4');
    if (!root || !home) return;

    const quoteUrl = home.getAttribute('data-quote-url');
    const sendInput = root.querySelector('[data-och4-send]');
    const receiveInput = root.querySelector('[data-och4-receive]');
    const fromCodeEl = root.querySelector('[data-och4-from-code]');
    const toCodeEl = root.querySelector('[data-och4-to-code]');
    const fromNameEl = root.querySelector('[data-och4-from-name]');
    const toNameEl = root.querySelector('[data-och4-to-name]');
    const fromUnitEl = root.querySelector('[data-och4-from-unit]');
    const toUnitEl = root.querySelector('[data-och4-to-unit]');
    const fromIcon = root.querySelector('[data-och4-from-icon]');
    const toIcon = root.querySelector('[data-och4-to-icon]');
    const swapBtn = root.querySelector('[data-och4-swap]');
    const usdtBalance = Number(home.getAttribute('data-usdt-balance') || 0);

    let from = 'USDT';
    let to = 'BTC';
    let timer = null;

    const names = { USDT: 'Tether', BTC: 'Bitcoin', TRX: 'Tron' };
    const iconFor = (code) => `/images/wallets/${String(code).toLowerCase()}.svg`;

    const syncUi = () => {
        if (fromCodeEl) fromCodeEl.textContent = from;
        if (toCodeEl) toCodeEl.textContent = to;
        if (fromNameEl) fromNameEl.textContent = names[from] || from;
        if (toNameEl) toNameEl.textContent = names[to] || to;
        if (fromUnitEl) fromUnitEl.textContent = from;
        if (toUnitEl) toUnitEl.textContent = to;
        if (fromIcon) fromIcon.src = iconFor(from);
        if (toIcon) toIcon.src = iconFor(to);
        const fromBal = root.querySelector('[data-och4-from-bal]');
        const toBal = root.querySelector('[data-och4-to-bal]');
        if (fromBal) {
            fromBal.textContent = from === 'USDT' ? usdtBalance.toFixed(2) : '0.000000';
        }
        if (toBal) {
            toBal.textContent = to === 'USDT' ? usdtBalance.toFixed(2) : '0.000000';
        }
    };

    const refreshQuote = async () => {
        if (!quoteUrl || !sendInput || !receiveInput) return;
        const amount = Number(sendInput.value || 0);
        if (!amount || amount <= 0 || from === to) {
            receiveInput.value = '—';
            return;
        }

        try {
            const params = new URLSearchParams({
                from_currency: from,
                to_currency: to,
                amount: String(amount),
            });
            const response = await fetch(`${quoteUrl}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                receiveInput.value = '—';
                return;
            }
            receiveInput.value = data.to_formatted || String(data.to_amount ?? '—');
        } catch (_) {
            receiveInput.value = '—';
        }
    };

    const schedule = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(refreshQuote, 280);
    };

    sendInput?.addEventListener('input', schedule);
    swapBtn?.addEventListener('click', () => {
        const prev = from;
        from = to;
        to = prev;
        syncUi();
        schedule();
    });

    // Cycle currencies on coin chip tap (USDT ↔ BTC ↔ TRX)
    const cycle = (current) => {
        const order = ['USDT', 'BTC', 'TRX'];
        const i = order.indexOf(current);
        return order[(i + 1) % order.length];
    };

    root.querySelector('[data-och4-from-btn]')?.addEventListener('click', () => {
        from = cycle(from);
        if (from === to) from = cycle(from);
        syncUi();
        schedule();
    });

    root.querySelector('[data-och4-to-btn]')?.addEventListener('click', () => {
        to = cycle(to);
        if (to === from) to = cycle(to);
        syncUi();
        schedule();
    });

    syncUi();
    schedule();
}
