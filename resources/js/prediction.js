function fmtMoney(n, decimals = 2) {
    const d = Math.max(0, Math.min(8, Number(decimals) || 2));
    return Number(n || 0).toFixed(d);
}

function fmtWithSymbol(symbol, amount, decimals = 2) {
    const sym = String(symbol || '$');
    const value = fmtMoney(amount, decimals);
    if (sym.length > 2 && /^[A-Z0-9]+$/i.test(sym)) {
        return `${value} ${sym}`;
    }
    return `${sym}${value}`;
}

function pad2(n) {
    return String(Math.max(0, Math.floor(n))).padStart(2, '0');
}

function clock(n) {
    return '00:' + pad2(n);
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

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('pred-app');
    if (!root) return;

    const stateUrl = root.dataset.stateUrl;
    const betUrl = root.dataset.betUrl;
    const cancelUrl = root.dataset.cancelUrl;
    const csrf = root.dataset.csrf;

    const amountEl = document.getElementById('pred-amount');
    const currencyBtn = document.getElementById('pred-currency-btn');
    const currencyMenu = document.getElementById('pred-currency-menu');
    const currencySymbolEl = document.getElementById('pred-currency-symbol');
    let playCurrency = amountEl?.dataset.playCurrency || 'USDT';
    let playSymbol = currencySymbolEl?.textContent?.trim() || '$';
    let playDecimals = Number(amountEl?.step?.includes('000001') ? 6 : 2);
    let playBalance = 0;
    let currenciesByCode = {};
    let localBetLeft = 0;
    let localRoundLeft = 0;
    let bettingOpen = true;
    let isIdle = true;
    let polling = false;
    let payout = 1.92;
    let winningOutcome = null;

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    // Same as App\Support\PredictionTrendRoad: 1–4 stack vertically.
    // 5th+ of the same outcome continue right from the 4th cell.
    function buildTrendRoad(items, maxHeight = 4) {
        const outcomes = (items || [])
            .map((item) => Number(item))
            .filter((n) => n === 1 || n === 2);
        const columns = [];
        let i = 0;
        while (i < outcomes.length) {
            const value = outcomes[i];
            let streak = 0;
            while (i < outcomes.length && outcomes[i] === value) {
                streak += 1;
                i += 1;
            }
            const startCol = columns.length;
            columns.push(Array(maxHeight).fill(null));
            for (let s = 0; s < streak; s += 1) {
                if (s < maxHeight) {
                    columns[startCol][s] = value;
                } else {
                    const colIdx = startCol + (s - maxHeight + 1);
                    while (columns.length <= colIdx) {
                        columns.push(Array(maxHeight).fill(null));
                    }
                    columns[colIdx][maxHeight - 1] = value;
                }
            }
        }
        return columns;
    }

    function renderTrendColumns(el, columns) {
        if (!columns.length) {
            el.innerHTML = '<span class="pred-live-trends-empty">Waiting for results…</span>';
            return;
        }
        el.innerHTML = columns.map((col) => (
            `<div class="pred-trend-col">${col.map((h) => (
                h == null
                    ? '<span class="pred-pill-spacer" aria-hidden="true"></span>'
                    : `<span class="pred-pill outcome-${h}">${h}</span>`
            )).join('')}</div>`
        )).join('');
        requestAnimationFrame(() => {
            el.scrollLeft = el.scrollWidth;
        });
    }

    function renderPills(containerId, history, limit, trendRoad) {
        const el = document.getElementById(containerId);
        if (!el) return;
        const items = (history || []).slice(limit ? -limit : 0);

        if (el.classList.contains('pred-live-trends')) {
            const columns = Array.isArray(trendRoad) && trendRoad.length
                ? trendRoad
                : buildTrendRoad(items, 4);
            renderTrendColumns(el, columns);
            return;
        }

        el.innerHTML = items.map((h) => `<span class="pred-pill outcome-${h}">${h}</span>`).join('');
    }

    function outcomeRevealHtml(settlePhase) {
        if (settlePhase && winningOutcome) {
            return `<span class="pred-outcome-reveal is-visible outcome-${winningOutcome}" id="pred-outcome-reveal">${winningOutcome}</span>`;
        }
        return '<span class="pred-outcome-reveal" id="pred-outcome-reveal"></span>';
    }

    function formatClock(totalSeconds) {
        const secs = Math.max(0, Number(totalSeconds) || 0);
        const m = Math.floor(secs / 60);
        const s = secs % 60;
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }

    function updatePlaceCountdown() {
        const phaseLabel = document.getElementById('pred-phase-label');
        const timerEl = document.getElementById('pred-timer');
        const revealHost = document.getElementById('pred-outcome-reveal');
        // Keep legacy node in sync if present (hidden).
        const legacy = document.getElementById('pred-place-countdown');

        if (isIdle) {
            if (phaseLabel) phaseLabel.textContent = 'WAITING';
            if (timerEl) timerEl.textContent = '—';
            if (revealHost) {
                revealHost.className = 'pred-outcome-reveal';
                revealHost.textContent = '';
            }
            if (legacy) legacy.innerHTML = '<span>PLACE A BET ON 1 OR 2 TO START</span>' + outcomeRevealHtml(false);
            return;
        }

        const betLeft = Math.max(0, localBetLeft);
        const roundLeft = Math.max(0, localRoundLeft);
        const settlePhase = !bettingOpen || betLeft <= 0;

        if (phaseLabel) {
            phaseLabel.textContent = settlePhase ? 'RESULT REVEAL' : 'BETTING CLOSES';
        }
        if (timerEl) {
            timerEl.textContent = formatClock(settlePhase ? roundLeft : betLeft);
        }

        const revealHtml = outcomeRevealHtml(settlePhase);
        if (revealHost) {
            const tmp = document.createElement('div');
            tmp.innerHTML = revealHtml;
            const next = tmp.firstElementChild;
            if (next) {
                revealHost.className = next.className;
                revealHost.textContent = next.textContent;
            }
        }
        if (legacy) {
            legacy.innerHTML = settlePhase
                ? `<span>ROUND OUTCOME IN</span><strong id="pred-place-round-secs">${formatClock(roundLeft)}</strong>${revealHtml}`
                : `<span>BETTING ENDS IN</span><strong id="pred-place-bet-secs">${formatClock(betLeft)}</strong>${revealHtml}`;
        }
    }

    function applyState(state) {
        const r = state.round;
        const conf = state.config;
        const stats = state.stats;
        payout = Number(conf.payout || 1.92);

        localBetLeft = r.betting_seconds_left || 0;
        localRoundLeft = r.round_seconds_left || 0;
        isIdle = !!state.idle || r.status === 'idle';
        bettingOpen = !isIdle && r.status === 'betting' && localBetLeft > 0;
        winningOutcome = r.winning_outcome ? Number(r.winning_outcome) : null;

        setText('pred-round', r.number === '—' || r.number == null ? '—' : '#' + r.number);
        updatePlaceCountdown();
        const copyRound = document.getElementById('pred-copy-round');
        if (copyRound) {
            const hasId = !isIdle && r.number != null && r.number !== '—';
            copyRound.disabled = !hasId;
            copyRound.title = hasId ? 'Copy Round ID' : 'Available when a round is active';
        }
        setText('pred-wallet', state.wallet_formatted || fmtWithSymbol(playSymbol, state.wallet, playDecimals));
        setText('pred-wallet-side', fmtMoney(state.wallet, playDecimals));
        setText('pred-pool', fmtMoney(stats.total_pool));
        setText('pred-pool-mobile', fmtMoney(stats.total_pool));
        setText('pred-pool-1', fmtMoney(stats.pool_1 ?? 0));
        setText('pred-pool-2', fmtMoney(stats.pool_2 ?? 0));
        // Keep dollar on shared USD pools (all stakes converted server-side).
        document.querySelectorAll('[data-pred-pool-prefix]').forEach((el) => {
            el.textContent = '$';
        });

        if (Array.isArray(state.currencies)) {
            currenciesByCode = {};
            state.currencies.forEach((c) => {
                currenciesByCode[c.code] = c;
            });
            syncCurrencyMenu(state.currencies);
        }

        if (state.user_bet?.currency) {
            applyCurrencySelection(state.user_bet.currency, { preserveAmount: true, fromState: true });
        } else if (currenciesByCode[playCurrency]) {
            applyCurrencySelection(playCurrency, { preserveAmount: true, fromState: true });
            if (typeof state.wallet === 'number') {
                playBalance = Number(state.wallet) || 0;
            }
        } else if (state.play_currency) {
            applyCurrencySelection(state.play_currency, { preserveAmount: true, fromState: true });
        }
        setText('pred-players-mobile', String(stats.total_players ?? 0));
        const playersEl = document.getElementById('pred-players');
        if (playersEl) {
            playersEl.textContent = String(stats.total_players ?? 0);
        }
        const playerRowsHtml = (() => {
            const rows = state.players || [];
            if (!rows.length) {
                return '<p class="pred-empty pred-players-empty">No players yet this round</p>';
            }
            return rows.map((p) => `
                <div class="pred-player-row">
                    <span class="pred-player-name" title="${escapeNoticeHtml(p.name)}">${escapeNoticeHtml(p.name)}</span>
                    <span class="pred-pill outcome-${Number(p.outcome)}">${Number(p.outcome)}</span>
                    <span class="pred-player-amt">${escapeNoticeHtml(p.formatted || fmtWithSymbol(p.symbol || '$', p.amount))}</span>
                </div>
            `).join('');
        })();
        const playersList = document.getElementById('pred-players-list');
        if (playersList) playersList.innerHTML = playerRowsHtml;
        const playersListMobile = document.getElementById('pred-players-list-mobile');
        if (playersListMobile) playersListMobile.innerHTML = playerRowsHtml;
        setText('pred-pct-1', stats.pct_1 + '%');
        setText('pred-pct-2', stats.pct_2 + '%');
        setText('pred-mult-1', payout.toFixed(2) + 'x');
        setText('pred-mult-2', payout.toFixed(2) + 'x');

        const hashFull = document.getElementById('pred-hash-full');
        const clientFull = document.getElementById('pred-client-full');
        const hashRaw = isIdle || !r.server_seed_hash || r.server_seed_hash === '—' ? '' : String(r.server_seed_hash);
        const clientRaw = isIdle || !r.client_seed || r.client_seed === '—' ? '' : String(r.client_seed);
        const nonceRaw = isIdle || r.nonce == null || r.nonce === '—' ? '' : String(r.nonce);

        if (hashFull) hashFull.textContent = hashRaw;
        if (clientFull) clientFull.textContent = clientRaw;
        setText('pred-hash', hashRaw ? hashRaw.slice(0, 16) + (hashRaw.length > 16 ? '…' : '') : '—');
        setText('pred-client', clientRaw ? clientRaw.slice(0, 12) + (clientRaw.length > 12 ? '…' : '') : '—');
        setText('pred-nonce', nonceRaw || '—');
        syncFairCopyButtons(isIdle, { server_seed_hash: hashRaw, client_seed: clientRaw, nonce: nonceRaw });
        const bar1 = document.getElementById('pred-bar-1');
        const bar2 = document.getElementById('pred-bar-2');
        if (bar1) bar1.style.width = stats.pct_1 + '%';
        if (bar2) bar2.style.width = stats.pct_2 + '%';

        const seedRow = document.getElementById('pred-seed-row');
        if (seedRow) {
            if (r.server_seed) {
                seedRow.style.display = '';
                setText('pred-seed', r.server_seed.slice(0, 14) + (r.server_seed.length > 14 ? '…' : ''));
            } else {
                seedRow.style.display = 'none';
            }
        }

        document.getElementById('pred-choice-1')?.classList.toggle('is-winner', r.winning_outcome === 1);
        document.getElementById('pred-choice-2')?.classList.toggle('is-winner', r.winning_outcome === 2);

        const status = document.getElementById('pred-status');
        if (status) {
            status.textContent = '';
        }

        document.querySelectorAll('.pred-pick').forEach((btn) => {
            btn.disabled = !bettingOpen;
        });
        const cancelBtn = document.getElementById('pred-cancel');
        if (cancelBtn) cancelBtn.disabled = !bettingOpen || !state.user_bet;

        const userBet = document.getElementById('pred-userbet');
        if (userBet) {
            if (state.user_bet) {
                const ub = state.user_bet;
                let extra = ub.status;
                if (ub.status === 'won' && (ub.payout_formatted || ub.payout != null)) {
                    extra += ` · +${ub.payout_formatted || fmtWithSymbol(ub.symbol || playSymbol, ub.payout)}`;
                }
                userBet.textContent = `Your bet: ${ub.formatted || fmtWithSymbol(ub.symbol || playSymbol, ub.amount)} on ${ub.outcome} (${extra})`;
                document.getElementById('pred-choice-1')?.classList.toggle('is-active-pick', ub.outcome === 1 && ub.status === 'active');
                document.getElementById('pred-choice-2')?.classList.toggle('is-active-pick', ub.outcome === 2 && ub.status === 'active');
            } else {
                userBet.textContent = '';
                document.getElementById('pred-choice-1')?.classList.remove('is-active-pick');
                document.getElementById('pred-choice-2')?.classList.remove('is-active-pick');
            }
        }
        renderPills('pred-trends', state.history, 50, state.trend_road);

        const myBetsEl = document.getElementById('pred-my-bets-list') || document.getElementById('pred-my-bets');
        const myBetsMobile = document.getElementById('pred-my-bets-mobile');
        const myBetsHtml = !state.my_bets?.length
            ? '<p class="pred-empty">No bets yet</p>'
            : state.my_bets.slice(0, 20).map((row) => {
                const resultClass = row.status === 'won'
                    ? 'pred-green'
                    : (row.status === 'lost' ? 'pred-loss' : 'pred-muted');
                const statusLabel = String(row.status || 'pending').toUpperCase();
                const mult = Number(row.payout_mult ?? (row.status === 'lost' ? 0 : payout));
                return `
                <div class="ocpd-bet-row">
                    <span>#${row.round ?? '—'}</span>
                    <span><span class="pred-pill outcome-${row.outcome}">${row.outcome}</span></span>
                    <span>${escapeNoticeHtml(row.formatted || fmtWithSymbol(row.symbol || '$', row.amount))}</span>
                    <span>${mult.toFixed(2)}x</span>
                    <span><em class="ocpd-status ocpd-status--${escapeNoticeHtml(row.status)}">${escapeNoticeHtml(statusLabel)}</em></span>
                    <span class="${resultClass}">${escapeNoticeHtml(row.result)}</span>
                    <span class="ocpd-row-chevron"><i class="fa-solid fa-chevron-right"></i></span>
                </div>`;
            }).join('');
        if (myBetsEl) myBetsEl.innerHTML = myBetsHtml;
        if (myBetsMobile) myBetsMobile.innerHTML = myBetsHtml;
    }

    async function fetchState() {
        if (polling) return;
        polling = true;
        try {
            const res = await fetch(stateUrl, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            applyState(data);
        } catch (e) {
            // ignore
        } finally {
            polling = false;
        }
    }

    async function placeBet(outcome) {
        if (!bettingOpen) return;
        const amount = Number(amountEl.value || 0);
        const res = await fetch(betUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ outcome, amount, currency: playCurrency }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            showAppNotice({
                title: 'Bet Failed',
                message: data?.errors?.amount?.[0] || data?.errors?.currency?.[0] || data?.message || 'Bet failed',
                variant: 'error',
            });
            return;
        }
        applyState(data.state);
    }

    async function cancelBet() {
        if (!bettingOpen) return;
        const res = await fetch(cancelUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            showAppNotice({
                title: 'Cancel Failed',
                message: data?.errors?.bet?.[0] || data?.message || 'Cancel failed',
                variant: 'error',
            });
            return;
        }
        applyState(data.state);
    }

    function currentWalletBalance() {
        if (playBalance > 0) return playBalance;
        const fromOption = currenciesByCode[playCurrency]?.balance;
        if (fromOption != null) return Number(fromOption) || 0;
        return Number((document.getElementById('pred-wallet-side')?.textContent || '0').replace(/[^0-9.]/g, '')) || 0;
    }

    function amountDecimals() {
        const meta = currenciesByCode[playCurrency];
        if (meta?.decimals != null) return Number(meta.decimals) || 2;
        return playDecimals || 2;
    }

    function applyCurrencySelection(code, { preserveAmount = false, fromState = false } = {}) {
        const meta = currenciesByCode[code];
        if (!meta && !fromState) return;

        playCurrency = code;
        playSymbol = meta?.symbol || playSymbol || '$';
        playDecimals = Number(meta?.decimals ?? playDecimals) || 2;
        playBalance = Number(meta?.balance ?? playBalance) || 0;

        if (currencySymbolEl) currencySymbolEl.textContent = playSymbol;
        if (amountEl) {
            amountEl.dataset.playCurrency = playCurrency;
            if (meta) {
                amountEl.min = String(meta.min_bet ?? amountEl.min);
                amountEl.max = String(meta.max_bet ?? amountEl.max);
                amountEl.step = String(meta.step || amountEl.step || '0.01');
            }
            if (!preserveAmount) {
                const max = Number(amountEl.max) || Infinity;
                const starter = Math.min(50, max);
                amountEl.value = fmtMoney(starter > 0 ? starter : 0, Math.min(playDecimals, 2));
            }
        }

        if (!fromState || meta) {
            setText('pred-wallet', meta?.formatted || fmtWithSymbol(playSymbol, playBalance, playDecimals));
            setText('pred-wallet-side', fmtMoney(playBalance, playDecimals));
        }

        currencyMenu?.querySelectorAll('.ocpd-currency-option').forEach((btn) => {
            btn.classList.toggle('is-selected', btn.dataset.currency === playCurrency);
        });

        // Don't close on live state polls — only after the player picks a currency.
        if (!fromState) {
            closeCurrencyMenu();
        }
    }

    function syncCurrencyMenu(list) {
        if (!currencyMenu || !list?.length) return;
        // Keep existing buttons in sync with live balances / limits from state.
        list.forEach((c) => {
            const btn = currencyMenu.querySelector(`[data-currency="${c.code}"]`);
            if (!btn) return;
            btn.dataset.symbol = c.symbol;
            btn.dataset.decimals = String(c.decimals);
            btn.dataset.min = String(c.min_bet);
            btn.dataset.max = String(c.max_bet);
            btn.dataset.step = String(c.step);
            btn.dataset.balance = String(c.balance);
            const small = btn.querySelector('small');
            if (small) small.textContent = `${c.symbol} · ${c.formatted}`;
        });
    }

    function openCurrencyMenu() {
        if (!currencyMenu || !currencyBtn) return;
        currencyMenu.hidden = false;
        currencyBtn.setAttribute('aria-expanded', 'true');
    }

    function closeCurrencyMenu() {
        if (!currencyMenu || !currencyBtn) return;
        currencyMenu.hidden = true;
        currencyBtn.setAttribute('aria-expanded', 'false');
    }

    currencyBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (currencyMenu?.hidden) openCurrencyMenu();
        else closeCurrencyMenu();
    });

    currencyMenu?.querySelectorAll('.ocpd-currency-option').forEach((btn) => {
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            applyCurrencySelection(btn.dataset.currency);
        });
    });

    document.addEventListener('pointerdown', (event) => {
        if (!currencyMenu || currencyMenu.hidden) return;
        const picker = event.target.closest('[data-pred-currency-picker]');
        if (!picker) closeCurrencyMenu();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeCurrencyMenu();
    });

    // Seed lookup map from server-rendered options.
    currencyMenu?.querySelectorAll('.ocpd-currency-option').forEach((btn) => {
        currenciesByCode[btn.dataset.currency] = {
            code: btn.dataset.currency,
            symbol: btn.dataset.symbol,
            decimals: Number(btn.dataset.decimals || 2),
            min_bet: Number(btn.dataset.min || 0),
            max_bet: Number(btn.dataset.max || 0),
            step: btn.dataset.step || '0.01',
            balance: Number(btn.dataset.balance || 0),
            formatted: btn.querySelector('small')?.textContent || '',
        };
    });
    if (currenciesByCode[playCurrency]) {
        applyCurrencySelection(playCurrency, { preserveAmount: true, fromState: true });
    }

    document.querySelectorAll('.pred-quick [data-mult]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const min = Number(amountEl.min);
            const floor = Number.isFinite(min) ? min : 0;
            const maxBet = Number(amountEl.max) || Infinity;
            const wallet = currentWalletBalance();
            const ceiling = Math.min(maxBet, wallet > 0 ? wallet : maxBet);
            const next = Number(amountEl.value || 0) * Number(btn.dataset.mult);
            amountEl.value = fmtMoney(Math.min(ceiling, Math.max(floor, next)), amountDecimals());
        });
    });

    document.getElementById('pred-free-btn')?.addEventListener('click', () => {
        amountEl.value = fmtMoney(0, Math.min(amountDecimals(), 2));
    });

    document.getElementById('pred-max-btn')?.addEventListener('click', () => {
        const wallet = currentWalletBalance();
        const max = Number(amountEl.max) || wallet;
        amountEl.value = fmtMoney(Math.min(wallet > 0 ? wallet : max, max), amountDecimals());
    });

    document.querySelectorAll('.pred-pick').forEach((btn) => {
        btn.addEventListener('click', () => placeBet(Number(btn.dataset.outcome)));
    });

    document.getElementById('pred-cancel')?.addEventListener('click', cancelBet);

    async function copyText(value) {
        const text = String(value || '').trim();
        if (!text || text === '—' || text === '-') return false;
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
                return true;
            }
        } catch (e) {
            // fall through to legacy copy
        }
        try {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            const ok = document.execCommand('copy');
            document.body.removeChild(ta);
            return ok;
        } catch (e) {
            return false;
        }
    }

    function flashCopyBtn(btn, ok) {
        if (!btn) return;
        const icon = btn.querySelector('i');
        if (!icon) return;
        const prev = icon.className;
        icon.className = ok ? 'fa-solid fa-check' : 'fa-solid fa-xmark';
        btn.classList.toggle('is-copied', ok);
        btn.classList.toggle('is-copy-fail', !ok);
        setTimeout(() => {
            icon.className = prev;
            btn.classList.remove('is-copied', 'is-copy-fail');
        }, 1200);
    }

    function syncFairCopyButtons(idle, r) {
        const hash = (r.server_seed_hash || '').trim();
        const client = (r.client_seed || '').trim();
        const nonce = String(r.nonce ?? '').trim();
        const hasRound = !idle && !!hash;

        document.querySelectorAll('[data-copy]').forEach((btn) => {
            const id = btn.getAttribute('data-copy');
            let ready = hasRound;
            if (id === 'pred-hash-full') ready = hasRound && !!hash;
            if (id === 'pred-client-full') ready = hasRound && !!client;
            if (id === 'pred-nonce') ready = hasRound && !!nonce;
            btn.disabled = !ready;
            btn.title = ready ? 'Copy' : 'Available when a round is active';
        });
    }

    document.getElementById('pred-copy-round')?.addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        if (btn.disabled) return;
        const t = (document.getElementById('pred-round')?.textContent || '').replace('#', '').trim();
        const ok = await copyText(t);
        flashCopyBtn(btn, ok);
    });

    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (btn.disabled) return;
            const id = btn.getAttribute('data-copy');
            const val = document.getElementById(id)?.textContent || '';
            const ok = await copyText(val);
            flashCopyBtn(btn, ok);
        });
    });

    // About / Game Info / More Stats modal
    const modal = document.getElementById('ocpd-modal');
    const openModal = (pane) => {
        if (!modal) return;
        modal.hidden = false;
        modal.classList.add('is-open');
        modal.querySelectorAll('[data-ocpd-pane]').forEach((el) => {
            el.hidden = el.getAttribute('data-ocpd-pane') !== pane;
        });
    };
    const closeModal = () => {
        if (!modal) return;
        modal.hidden = true;
        modal.classList.remove('is-open');
    };
    document.querySelectorAll('[data-ocpd-open]').forEach((btn) => {
        btn.addEventListener('click', () => openModal(btn.getAttribute('data-ocpd-open')));
    });
    document.querySelectorAll('[data-ocpd-close]').forEach((btn) => {
        btn.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });

    setInterval(() => {
        if (localBetLeft > 0) localBetLeft -= 1;
        if (localRoundLeft > 0) localRoundLeft -= 1;
        if (localBetLeft <= 0) bettingOpen = false;
        updatePlaceCountdown();
        if (localBetLeft <= 0 || localRoundLeft <= 0) fetchState();
    }, 1000);

    setInterval(fetchState, 1000);
    fetchState();
});
