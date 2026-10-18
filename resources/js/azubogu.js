/**
 * Azubogu Prediction Game — live poll + auto-bet on number select
 */
(function () {
    const app = document.getElementById('azu-app');
    if (!app) return;

    const stateUrl = app.dataset.stateUrl;
    const betUrl = app.dataset.betUrl;
    const cancelUrl = app.dataset.cancelUrl;
    const csrf = app.dataset.csrf;

    let selected = [];
    let state = {};
    let pollTimer = null;
    let localTick = null;
    let busy = false;
    let autoBetTimer = null;
    let autoBetSeq = 0;
    let trackedRoundId = null;

    try {
        state = JSON.parse(document.getElementById('azu-initial-state')?.textContent || '{}');
    } catch (_) {
        state = {};
    }

    const el = (id) => document.getElementById(id);

    function formatMoney(n) {
        return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatTimer(seconds) {
        const s = Math.max(0, Math.floor(seconds || 0));
        const m = Math.floor(s / 60);
        const r = s % 60;
        return String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
    }

    function secondsLeftFromIso(iso) {
        if (!iso) return 0;
        const end = Date.parse(iso);
        if (Number.isNaN(end)) return 0;
        return Math.max(0, Math.ceil((end - Date.now()) / 1000));
    }

    function multClass(m) {
        const v = Number(m);
        if (Math.abs(v - 1.9) < 0.001) return 'is-reveal-190';
        if (Math.abs(v - 1.4) < 0.001) return 'is-reveal-140';
        if (Math.abs(v - 0.45) < 0.001) return 'is-reveal-045';
        return 'is-reveal-000';
    }

    function sameNumbers(a, b) {
        const left = (a || []).map(Number).slice().sort((x, y) => x - y);
        const right = (b || []).map(Number).slice().sort((x, y) => x - y);
        return left.length === right.length && left.every((n, i) => n === right[i]);
    }

    function updateSelectionUI() {
        const count = el('azu-sel-count');
        const selectedBox = el('azu-selected');
        if (count) count.textContent = selected.length + '/3 SELECTED';
        if (selectedBox) {
            selectedBox.textContent = selected.length
                ? selected.slice().sort((a, b) => a - b).join(', ')
                : '';
        }

        document.querySelectorAll('.azu-num').forEach((btn) => {
            const n = Number(btn.dataset.num);
            btn.setAttribute('aria-pressed', selected.includes(n) ? 'true' : 'false');
        });
    }

    function applyReveal(multipliers) {
        document.querySelectorAll('.azu-num').forEach((btn) => {
            btn.classList.remove('is-reveal-190', 'is-reveal-140', 'is-reveal-045', 'is-reveal-000');
            if (!multipliers) return;
            const m = multipliers[btn.dataset.num] ?? multipliers[String(btn.dataset.num)];
            if (m === undefined || m === null) return;
            btn.classList.add(multClass(m));
        });
    }

    function renderResultGroups(groups) {
        document.querySelectorAll('.azu-leg[data-group]').forEach((card) => {
            const key = card.getAttribute('data-group');
            const dots = card.querySelector('.azu-dots');
            const numsEl = card.querySelector('.azu-leg-nums');
            if (!numsEl) return;

            if (!groups) {
                numsEl.hidden = true;
                numsEl.textContent = '';
                if (dots) dots.hidden = false;
                return;
            }

            const nums = groups[key] || [];
            numsEl.hidden = false;
            numsEl.textContent = nums.length ? nums.join(', ') : '—';
            if (dots) dots.hidden = true;
        });
    }

    function renderHistory(history) {
        const body = el('azu-history-body');
        if (!body) return;

        const rows = (history || []).slice(0, 5);

        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="6" class="azu-empty">No completed rounds yet</td></tr>';
            return;
        }

        body.innerHTML = rows.map((h) => {
            const g = h.groups || {};
            return `<tr>
                <td data-label="Time">${h.time || '—'}</td>
                <td data-label="Round">#${h.round_number}</td>
                <td data-label="1.90×" class="c-190">${(g['1.90x'] || []).join(', ')}</td>
                <td data-label="1.40×" class="c-140">${(g['1.40x'] || []).join(', ')}</td>
                <td data-label="0.45×" class="c-045">${(g['0.45x'] || []).join(', ')}</td>
                <td data-label="0×" class="c-0">${(g['0x'] || []).join(', ')}</td>
            </tr>`;
        }).join('');
    }

    function renderMyBets(bets) {
        const body = el('azu-mybets-body');
        if (!body) return;

        const rows = (bets || []).slice(0, 5);
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="5" class="azu-empty">No personal bets yet</td></tr>';
            return;
        }

        body.innerHTML = rows.map((b) => {
            const status = b.status || 'active';
            let resultHtml = '<span class="azu-outcome active">Active</span>';
            let payout = '—';

            if (status === 'won') {
                resultHtml = `<span class="azu-outcome won">Won ${Number(b.multiplier || 0).toFixed(2)}×</span>`;
                payout = '$' + formatMoney(b.payout);
            } else if (status === 'lost') {
                resultHtml = '<span class="azu-outcome lost">Lost</span>';
                payout = '$' + formatMoney(b.payout);
            }

            return `<tr>
                <td data-label="Round">#${b.round || '—'}</td>
                <td data-label="Numbers">${(b.numbers || []).join(', ') || '—'}</td>
                <td data-label="Bet">$${formatMoney(b.amount)}</td>
                <td data-label="Result">${resultHtml}</td>
                <td data-label="Payout">${payout}</td>
            </tr>`;
        }).join('');
    }

    function paintTimer() {
        const round = state.round || {};
        const betting = (round.status || 'betting') === 'betting';
        const left = betting
            ? (round.betting_ends_at ? secondsLeftFromIso(round.betting_ends_at) : (round.betting_seconds_left ?? 0))
            : (round.round_ends_at ? secondsLeftFromIso(round.round_ends_at) : (round.round_seconds_left ?? 0));

        if (el('azu-timer')) el('azu-timer').textContent = formatTimer(left);
        if (el('azu-phase-label')) {
            el('azu-phase-label').textContent = betting ? 'BETTING CLOSES' : 'RESULT REVEAL';
        }
    }

    function applyState(next) {
        if (busy && next && !next.user_bet && state.user_bet) {
            next = { ...next, user_bet: state.user_bet, wallet: state.wallet };
        }

        state = next || {};
        const round = state.round || {};
        const status = round.status || 'betting';
        const betting = status === 'betting';
        const roundId = round.id ?? round.round_number ?? null;

        // New round → clear previous picks so highlights don't carry over.
        if (trackedRoundId !== null && roundId !== null && trackedRoundId !== roundId) {
            selected = [];
            clearTimeout(autoBetTimer);
        }
        if (roundId !== null) {
            trackedRoundId = roundId;
        }

        if (el('azu-round')) el('azu-round').textContent = round.round_number ? '#' + round.round_number : '—';
        if (el('azu-players')) el('azu-players').textContent = String(state.stats?.players ?? 0);
        if (el('azu-pool')) el('azu-pool').textContent = '$' + formatMoney(state.stats?.total_pool);
        if (el('azu-wallet')) el('azu-wallet').textContent = formatMoney(state.wallet);

        paintTimer();

        const hash = round.server_seed_hash || '—';
        if (el('azu-hash')) el('azu-hash').textContent = hash.length > 24 ? hash.slice(0, 24) + '…' : hash;
        if (el('azu-nonce')) el('azu-nonce').textContent = round.nonce ?? '—';
        if (el('azu-seed-wrap')) {
            el('azu-seed-wrap').style.display = round.server_seed ? '' : 'none';
            if (el('azu-seed') && round.server_seed) {
                el('azu-seed').textContent = round.server_seed.slice(0, 16) + (round.server_seed.length > 16 ? '…' : '');
            }
        }
        if (el('azu-verified')) {
            el('azu-verified').innerHTML = betting
                ? '<i class="fa-solid fa-lock"></i> LOCKED'
                : '<i class="fa-solid fa-circle-check"></i> VERIFIED';
        }

        applyReveal(betting ? null : round.multipliers);
        renderResultGroups(betting ? null : round.groups);
        renderHistory(state.history || []);
        renderMyBets(state.my_bets || []);

        const userBet = state.user_bet;
        const cancelBtn = el('azu-cancel');
        const statusEl = el('azu-bet-status');
        const amountInput = el('azu-amount');

        // Numbers stay selectable during betting so picks place/update the bet.
        document.querySelectorAll('.azu-num').forEach((btn) => {
            btn.disabled = !betting || busy;
        });

        if (amountInput) {
            amountInput.disabled = busy || !betting || !!userBet;
        }
        document.querySelectorAll('.azu-chip').forEach((chip) => {
            chip.disabled = busy || !betting || !!userBet;
        });

        if (userBet) {
            // Show this round's bet picks only while that round is live.
            selected = (userBet.numbers || []).map(Number);
            updateSelectionUI();
            if (cancelBtn) cancelBtn.hidden = !(betting && userBet.status === 'active' && !busy);
            if (statusEl) {
                if (userBet.status === 'active') {
                    statusEl.textContent = busy
                        ? 'Updating bet…'
                        : `Active bet: $${formatMoney(userBet.amount)} on ${selected.join(', ')}`;
                } else if (userBet.status === 'won') {
                    statusEl.textContent = `Won $${formatMoney(userBet.payout)} (${userBet.multiplier}×)`;
                } else {
                    statusEl.textContent = `Lost — payout $0`;
                }
            }
        } else {
            if (!betting) {
                selected = [];
            }
            if (cancelBtn) cancelBtn.hidden = true;
            if (statusEl && betting) {
                statusEl.textContent = busy ? 'Placing bet…' : '';
            }
            if (!betting && statusEl) statusEl.textContent = 'Waiting for next round…';
            updateSelectionUI();
        }
    }

    async function fetchState() {
        if (busy) return;
        try {
            const res = await fetch(stateUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            applyState(await res.json());
        } catch (_) {
            /* ignore transient poll errors */
        }
    }

    async function postJson(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const msg = data?.message
                || data?.errors?.amount?.[0]
                || data?.errors?.numbers?.[0]
                || data?.errors?.bet?.[0]
                || 'Request failed';
            throw new Error(msg);
        }
        return data;
    }

    function showError(msg) {
        const err = el('azu-error');
        if (!err) return;
        err.hidden = !msg;
        err.textContent = msg || '';
    }

    async function placeOrUpdateBet(numbers) {
        const amount = state.user_bet
            ? Number(state.user_bet.amount || 0)
            : Number(el('azu-amount')?.value || 0);

        if (!Number.isFinite(amount) || amount < 0) {
            showError('Enter a valid bet amount first (including $0 play).');
            return;
        }

        if (state.user_bet && sameNumbers(state.user_bet.numbers, numbers)) {
            return;
        }

        const prevState = state;
        const prevWallet = Number(state.wallet || 0);
        const isNewBet = !state.user_bet;
        const seq = ++autoBetSeq;

        busy = true;
        showError('');

        if (isNewBet) {
            applyState({
                ...state,
                wallet: amount > 0 ? Math.max(0, prevWallet - amount) : prevWallet,
                stats: amount > 0
                    ? {
                        ...(state.stats || {}),
                        total_pool: Number(state.stats?.total_pool || 0) + amount,
                        players: Math.max(Number(state.stats?.players || 0), 1),
                    }
                    : {
                        ...(state.stats || {}),
                        players: Math.max(Number(state.stats?.players || 0), 1),
                    },
                user_bet: {
                    numbers,
                    amount,
                    payout: 0,
                    multiplier: 0,
                    status: 'active',
                },
            });
        } else {
            applyState({
                ...state,
                user_bet: {
                    ...state.user_bet,
                    numbers,
                    status: 'active',
                },
            });
        }

        try {
            const data = await postJson(betUrl, { numbers, amount });
            if (seq !== autoBetSeq) return;
            busy = false;
            if (data.state) applyState(data.state);
            else await fetchState();
            if (!sameNumbers(selected, state.user_bet?.numbers || []) && selected.length > 0) {
                scheduleAutoBet();
            } else if (selected.length < 1 && state.user_bet) {
                scheduleAutoBet();
            }
        } catch (err) {
            if (seq !== autoBetSeq) return;
            busy = false;
            applyState(prevState);
            selected = numbers;
            updateSelectionUI();
            showError(err.message || 'Bet failed');
        }
    }

    async function cancelActiveBet() {
        const prevState = state;
        const seq = ++autoBetSeq;

        busy = true;
        showError('');
        applyState({
            ...state,
            user_bet: null,
        });

        try {
            const data = await postJson(cancelUrl, {});
            if (seq !== autoBetSeq) return;
            busy = false;
            if (data.state) applyState(data.state);
            else await fetchState();
        } catch (err) {
            if (seq !== autoBetSeq) return;
            busy = false;
            applyState(prevState);
            showError(err.message || 'Cancel failed');
        }
    }

    function scheduleAutoBet() {
        clearTimeout(autoBetTimer);
        autoBetTimer = setTimeout(async () => {
            const betting = (state.round?.status || 'betting') === 'betting';
            if (!betting) return;
            if (busy) {
                scheduleAutoBet();
                return;
            }

            const numbers = selected.slice().sort((a, b) => a - b);

            if (numbers.length < 1) {
                if (state.user_bet) await cancelActiveBet();
                return;
            }

            await placeOrUpdateBet(numbers);
        }, 280);
    }

    document.getElementById('azu-numbers')?.addEventListener('click', (e) => {
        const btn = e.target.closest('.azu-num');
        if (!btn || btn.disabled || busy) return;

        const betting = (state.round?.status || 'betting') === 'betting';
        if (!betting) return;

        const n = Number(btn.dataset.num);
        if (selected.includes(n)) {
            selected = selected.filter((x) => x !== n);
        } else if (selected.length < 3) {
            selected = [...selected, n];
        } else {
            return;
        }

        updateSelectionUI();
        showError('');
        scheduleAutoBet();
    });

    document.querySelectorAll('.azu-chip[data-mult]').forEach((chip) => {
        chip.addEventListener('click', () => {
            const input = el('azu-amount');
            if (!input || input.disabled || busy || state.user_bet) return;
            const cur = Number(input.value || 0);
            const factor = Number(chip.dataset.mult);
            const min = Number(input.min);
            const floor = Number.isFinite(min) ? min : 0;
            input.value = Math.max(floor, Math.round(cur * factor * 100) / 100).toFixed(2);
        });
    });

    el('azu-free-btn')?.addEventListener('click', () => {
        const input = el('azu-amount');
        if (!input || input.disabled || busy || state.user_bet) return;
        input.value = '0.00';
    });

    el('azu-cancel')?.addEventListener('click', async () => {
        if (busy) return;
        clearTimeout(autoBetTimer);
        selected = [];
        updateSelectionUI();
        await cancelActiveBet();
    });

    const modal = el('azu-modal');
    function openAzuModal(name) {
        if (!modal) return;
        modal.hidden = false;
        modal.classList.add('is-open');
        modal.querySelectorAll('[data-azu-pane]').forEach((pane) => {
            pane.hidden = pane.getAttribute('data-azu-pane') !== name;
        });
    }
    function closeAzuModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.hidden = true;
    }
    document.querySelectorAll('[data-azu-open]').forEach((btn) => {
        btn.addEventListener('click', () => openAzuModal(btn.getAttribute('data-azu-open')));
    });
    document.querySelectorAll('[data-azu-close]').forEach((btn) => {
        btn.addEventListener('click', closeAzuModal);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAzuModal();
    });

    applyState(state);
    pollTimer = setInterval(fetchState, 1000);
    localTick = setInterval(paintTimer, 250);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') fetchState();
    });
})();
