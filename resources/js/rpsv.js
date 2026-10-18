function money(currency, amount) {
  const n = Number(amount || 0);
  const prefix = currency === 'NGN' ? '₦' : '$';
  return prefix + n.toFixed(2);
}

function ensureRpsvPopup() {
  let popup = document.querySelector('[data-rpsv-popup]');
  if (popup) {
    if (popup.parentElement !== document.body) {
      document.body.appendChild(popup);
    }
    return popup;
  }
  popup = document.createElement('div');
  popup.className = 'rpsv-popup';
  popup.setAttribute('data-rpsv-popup', '');
  popup.hidden = true;
  popup.innerHTML = '<div class="rpsv-popup-card"><strong data-popup-title></strong><p data-popup-message></p></div>';
  document.body.appendChild(popup);
  return popup;
}

function showRpsvPopup(root, title, message, ms = 3500) {
  const popup = ensureRpsvPopup();
  if (!popup) return;
  const t = popup.querySelector('[data-popup-title]');
  const m = popup.querySelector('[data-popup-message]');
  if (t) t.textContent = title || '';
  if (m) m.textContent = message || '';
  popup.hidden = false;
  popup.style.display = 'grid';
  window.clearTimeout(showRpsvPopup._timer);
  showRpsvPopup._timer = window.setTimeout(() => {
    popup.hidden = true;
    popup.style.display = '';
  }, ms);
}

function readJsonScript(selector) {
  const el = document.querySelector(selector);
  if (!el) return null;
  try {
    return JSON.parse((el.textContent || '').trim() || 'null');
  } catch (e) {
    return null;
  }
}

function popupFromQuery() {
  try {
    const q = new URLSearchParams(window.location.search);
    if (q.get('invite_sent') !== '1') return null;
    const to = q.get('to') || 'your friend';
    const stake = q.get('stake') || 'a match';
    // Clean URL so refresh does not re-show
    q.delete('invite_sent');
    q.delete('to');
    q.delete('stake');
    const next = `${window.location.pathname}${q.toString() ? `?${q}` : ''}${window.location.hash || ''}`;
    window.history.replaceState({}, '', next);
    return {
      title: 'Invitation sent',
      message: `You invited ${to} for ${stake}.`,
    };
  } catch (e) {
    return null;
  }
}

function refreshHeaderBalance(formatted, code) {
  const el = document.querySelector('[data-dash-balance-value]');
  if (el && formatted) {
    el.dataset.balanceVisible = formatted;
    if (!el.classList.contains('is-hidden')) {
      el.textContent = formatted;
    }
  }
  if (code) {
    const codeEl = document.querySelector('[data-dash-balance-code]');
    if (codeEl) codeEl.textContent = code;
  }
}

function refreshNotifBadge(count) {
  const n = Number(count || 0);
  let badge = document.getElementById('notification-badge');
  const toggle = document.getElementById('notification-dropdown-toggle');
  if (n <= 0) {
    if (badge) badge.remove();
    return;
  }
  if (!badge && toggle) {
    badge = document.createElement('span');
    badge.className = 'badge';
    badge.id = 'notification-badge';
    toggle.appendChild(badge);
  }
  if (badge) badge.textContent = n > 9 ? '9+' : String(n);
}

function initLobby() {
  const root = document.querySelector('[data-rpsv-lobby]');
  if (!root || root.dataset.rpsvLobbyReady === '1') return;
  root.dataset.rpsvLobbyReady = '1';

  const currencyInputs = root.querySelectorAll('[data-rpsv-currency-input]');
  const categoryTabs = root.querySelectorAll('[data-rpsv-category] [data-category]');
  const stakeShow = root.querySelector('[data-rpsv-stake-show]');
  const winShow = root.querySelector('[data-rpsv-win-show]');
  const inviteStake = root.querySelector('[data-invite-stake]');
  const inviteFriend = root.querySelector('[data-invite-friend]');
  const invitePot = root.querySelector('[data-invite-pot]');
  const inviteWin = root.querySelector('[data-invite-win]');
  const inviteFee = root.querySelector('[data-invite-fee]');
  const inviteSelect = root.querySelector('[data-rpsv-invite-stake]');
  const walletHeading = root.querySelector('[data-rpsv-wallet-heading]');
  const walletValue = root.querySelector('[data-rpsv-wallet-value]');
  const coinOpen = root.querySelector('[data-rpsv-coin-open]');
  const coinMenu = root.querySelector('[data-rpsv-coin-menu]');
  const fiatCoin = root.querySelector('[data-rpsv-fiat-coin]');
  const coinIcon = root.querySelector('[data-rpsv-coin-icon]');
  const coinCode = root.querySelector('[data-rpsv-coin-code]');
  const depositLink = root.querySelector('[data-rpsv-deposit]');
  const inviteCurLabel = root.querySelector('[data-invite-cur-label]');
  const selectCurrencyUrl = root.querySelector('[data-select-currency-url]')?.textContent?.trim() || '';
  const csrf = root.dataset.csrf || '';

  let category = root.querySelector('[data-rpsv-currency-panel]')?.dataset.category || 'crypto';
  let currency = root.querySelector('[data-active-currency]')?.textContent?.trim() || 'USD';
  let cryptoWallets = readJsonScript('[data-rpsv-crypto-boot]') || [];
  let ngnBal = root.querySelector('[data-ngn-bal]')?.textContent || '₦0.00';

  const stakesBoot = readJsonScript('[data-rpsv-stakes-boot]') || { USD: [10], NGN: [25] };
  const defaultStake = { USD: 10, NGN: 25 };
  const selectedStakeByCategory = { crypto: defaultStake.USD, fiat: defaultStake.NGN };
  let stakesBuiltFor = null;

  const stakeBucket = () => (category === 'fiat' ? 'NGN' : 'USD');
  const displayCurrency = () => (category === 'fiat' ? 'NGN' : 'USD');

  const formatStakeLabel = (amount) => {
    const n = Number(amount);
    return n < 1 ? n.toFixed(2) : String(Math.round(n));
  };

  const rememberStake = (amount) => {
    const n = Number(amount);
    if (!Number.isFinite(n)) return;
    selectedStakeByCategory[category] = n;
  };

  const currentWallet = () => {
    if (category === 'fiat') {
      return { code: 'NGN', match_code: 'NGN', formatted: ngnBal, icon: '' };
    }
    return cryptoWallets.find((w) => w.match_code === currency || w.code === currency)
      || cryptoWallets.find((w) => w.code === 'USDT')
      || cryptoWallets[0]
      || { code: 'USDT', match_code: 'USD', formatted: '$0.00', icon: '' };
  };

  const rebuildStakes = (force = false) => {
    const bucket = stakeBucket();
    if (!force && stakesBuiltFor === `${category}:${bucket}`) return;
    const list = stakesBoot[bucket] || stakesBoot.USD || [];
    let preferred = Number(selectedStakeByCategory[category] ?? defaultStake[bucket] ?? list[0]);
    if (!list.some((s) => Number(s) === preferred)) {
      preferred = Number(defaultStake[bucket] ?? list[0]);
      selectedStakeByCategory[category] = preferred;
    }
    const stakesEl = root.querySelector('[data-rpsv-stakes]');
    if (stakesEl) {
      const sym = bucket === 'NGN' ? '₦' : '$';
      stakesEl.innerHTML = list.map((stake) => {
        const active = Number(stake) === preferred;
        return `<label class="${active ? 'is-active' : ''}">
          <input type="radio" name="stake" value="${stake}" ${active ? 'checked' : ''} required>
          <span><em data-cur>${sym}</em>${formatStakeLabel(stake)}${active ? '<b class="rpsv-check">✓</b>' : ''}</span>
        </label>`;
      }).join('');
      stakesEl.querySelectorAll('label').forEach((label) => {
        const apply = () => {
          const input = label.querySelector('input[type="radio"]');
          if (input) {
            input.checked = true;
            rememberStake(input.value);
          }
          stakesEl.querySelectorAll('label').forEach((l) => {
            l.classList.remove('is-active');
            const check = l.querySelector('.rpsv-check');
            if (check) check.remove();
          });
          label.classList.add('is-active');
          if (!label.querySelector('.rpsv-check')) {
            const span = label.querySelector('span');
            if (span) span.insertAdjacentHTML('beforeend', '<b class="rpsv-check">✓</b>');
          }
          updateQuick();
        };
        label.addEventListener('click', apply);
        label.querySelector('input')?.addEventListener('change', apply);
      });
    }
    if (inviteSelect) {
      inviteSelect.innerHTML = list.map((stake) => (
        `<option value="${stake}" ${Number(stake) === preferred ? 'selected' : ''}>${formatStakeLabel(stake)}</option>`
      )).join('');
      rememberStake(inviteSelect.value);
    }
    stakesBuiltFor = `${category}:${bucket}`;
  };

  const closeCoinMenu = () => {
    if (!coinMenu) return;
    coinMenu.hidden = true;
    if (coinOpen) coinOpen.setAttribute('aria-expanded', 'false');
  };

  const syncWalletUi = () => {
    const wallet = currentWallet();
    currency = category === 'fiat' ? 'NGN' : (wallet.match_code || wallet.code || 'USD');
    currencyInputs.forEach((el) => { el.value = currency; });
    if (walletHeading) walletHeading.textContent = category === 'fiat' ? 'FIAT WALLET' : 'CRYPTO WALLET';
    if (walletValue) walletValue.textContent = wallet.formatted || (category === 'fiat' ? ngnBal : '$0.00');
    if (coinOpen) coinOpen.hidden = category === 'fiat';
    if (fiatCoin) fiatCoin.hidden = category !== 'fiat';
    if (coinCode) coinCode.textContent = wallet.code || 'USDT';
    if (coinIcon && wallet.icon) coinIcon.src = wallet.icon;
    if (depositLink) {
      const code = category === 'fiat' ? 'USDT' : (wallet.code || 'USDT');
      depositLink.href = `/dashboard/deposit?currency=${encodeURIComponent(code)}`;
    }
    if (inviteCurLabel) inviteCurLabel.textContent = displayCurrency();
    root.querySelectorAll('[data-cur]').forEach((el) => {
      el.textContent = category === 'fiat' ? '₦' : '$';
    });
    coinMenu?.querySelectorAll('.rpsv-coin-option').forEach((btn) => {
      btn.classList.toggle('is-selected', btn.dataset.matchCode === currency || btn.dataset.code === currency);
    });
    const headerCode = category === 'fiat' ? 'NGN' : (wallet.code || 'USDT');
    const headerBal = wallet.formatted || (category === 'fiat' ? ngnBal : '$0.00');
    refreshHeaderBalance(headerBal, headerCode);
  };

  const persistPreferred = async (code) => {
    if (!selectCurrencyUrl || !code) return;
    try {
      await fetch(selectCurrencyUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ currency: code }),
      });
    } catch (e) {
      // Non-blocking — local UI already updated.
    }
  };

  const syncCategory = ({ rebuild = true } = {}) => {
    categoryTabs.forEach((btn) => btn.classList.toggle('is-active', btn.dataset.category === category));
    const panel = root.querySelector('[data-rpsv-currency-panel]');
    if (panel) panel.dataset.category = category;
    if (category === 'fiat') {
      currency = 'NGN';
    } else if (currency === 'NGN') {
      currency = cryptoWallets.find((w) => w.code === 'USDT')?.match_code || 'USD';
    }
    syncWalletUi();
    if (rebuild) rebuildStakes(true);
    updateQuick();
    updateInvite();
    closeCoinMenu();
  };

  const selectedQuickStake = () => {
    const checked = root.querySelector('.rpsv-quick-form input[name="stake"]:checked');
    if (checked) {
      rememberStake(checked.value);
      return Number(checked.value);
    }
    return Number(selectedStakeByCategory[category] ?? defaultStake[stakeBucket()] ?? 10);
  };

  const updateQuick = () => {
    const stake = selectedQuickStake();
    const cur = displayCurrency();
    if (stakeShow) stakeShow.textContent = money(cur, stake);
    if (winShow) winShow.textContent = money(cur, stake * 1.9);
  };

  const updateInvite = () => {
    const stake = inviteSelect
      ? Number(inviteSelect.value)
      : Number(selectedStakeByCategory[category] ?? defaultStake[stakeBucket()] ?? 20);
    if (inviteSelect) rememberStake(inviteSelect.value);
    const cur = displayCurrency();
    if (inviteStake) inviteStake.textContent = money(cur, stake);
    if (inviteFriend) inviteFriend.textContent = money(cur, stake);
    if (invitePot) invitePot.textContent = money(cur, stake * 2);
    if (inviteWin) inviteWin.textContent = money(cur, stake * 1.9);
    if (inviteFee) inviteFee.textContent = money(cur, stake * 2 * 0.05);
  };

  categoryTabs.forEach((btn) => {
    btn.addEventListener('click', () => {
      category = btn.dataset.category || 'crypto';
      const preferredCode = category === 'fiat' ? 'NGN' : (currentWallet().code || 'USDT');
      persistPreferred(preferredCode);
      syncCategory({ rebuild: true });
    });
  });

  coinOpen?.addEventListener('click', (event) => {
    event.stopPropagation();
    if (!coinMenu) return;
    const open = coinMenu.hidden;
    coinMenu.hidden = !open;
    coinOpen.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  coinMenu?.querySelectorAll('.rpsv-coin-option').forEach((btn) => {
    btn.addEventListener('click', () => {
      const code = btn.dataset.code || 'USDT';
      const matchCode = btn.dataset.matchCode || code;
      const formatted = btn.dataset.formatted || '';
      const icon = btn.dataset.icon || '';
      cryptoWallets = cryptoWallets.map((w) => (
        w.code === code ? { ...w, formatted: formatted || w.formatted, icon: icon || w.icon } : w
      ));
      currency = matchCode;
      category = 'crypto';
      persistPreferred(code);
      syncCategory({ rebuild: true });
      closeCoinMenu();
    });
  });

  document.addEventListener('click', (event) => {
    if (!coinMenu || coinMenu.hidden) return;
    if (event.target.closest('[data-rpsv-wallet-block]')) return;
    closeCoinMenu();
  });

  inviteSelect?.addEventListener('change', () => {
    rememberStake(inviteSelect.value);
    updateInvite();
  });
  syncCategory({ rebuild: true });

  const activateTab = (tab) => {
    root.querySelectorAll('[data-invite-tabs] button').forEach((b) => {
      b.classList.toggle('is-active', b.dataset.tab === tab);
    });
    root.querySelectorAll('[data-tab-panel]').forEach((panel) => {
      panel.hidden = panel.getAttribute('data-tab-panel') !== tab;
    });
  };

  root.querySelectorAll('[data-invite-tabs] button').forEach((btn) => {
    btn.addEventListener('click', () => activateTab(btn.dataset.tab || 'send'));
  });

  activateTab(root.dataset.inviteTab || 'send');

  // Sender: session boot JSON, or ?invite_sent=1 query fallback
  const bootPopup = readJsonScript('[data-rpsv-invite-popup-boot]') || popupFromQuery();
  if (bootPopup && typeof bootPopup === 'object') {
    showRpsvPopup(root, bootPopup.title || 'Invitation sent', bootPopup.message || '', 3500);
  }

  const incomingWrap = root.querySelector('[data-incoming-wrap]');
  const incomingList = root.querySelector('[data-incoming-list]');
  const incomingMine = root.querySelector('[data-incoming-mine]');
  const outgoingList = root.querySelector('[data-outgoing-list]');

  const formRow = (url, label, className) => (
    `<form method="POST" action="${url}" data-rpsv-invite-accept="${label === 'Accept' ? '1' : '0'}"><input type="hidden" name="_token" value="${csrf}"><button class="${className}" type="submit">${label}</button></form>`
  );

  const shownIncomingKey = 'rpsv_shown_incoming_v3';
  const shownIncoming = new Set(JSON.parse(sessionStorage.getItem(shownIncomingKey) || '[]'));
  const announcedThisPage = new Set();
  let redirectingToMatch = false;
  let pollTimer = null;
  let pollBusy = false;
  let hasPendingInvites = false;

  const goToMatch = (url) => {
    if (!url || redirectingToMatch) return;
    redirectingToMatch = true;
    showRpsvPopup(root, 'Match ready', 'Opening match…', 8000);
    window.location.href = url;
  };

  const acceptInvite = async (form, button) => {
    if (redirectingToMatch || !form?.action) return;
    const label = button?.textContent || 'Accept';
    if (button) {
      button.disabled = true;
      button.textContent = 'Joining…';
    }
    root.querySelectorAll('[data-rpsv-invite-accept="1"] button').forEach((btn) => {
      btn.disabled = true;
    });
    showRpsvPopup(root, 'Accepting invite', 'Locking stake and opening match…', 8000);

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf,
        },
        body: new FormData(form),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) {
        const msg = data?.message
          || data?.errors?.invite?.[0]
          || data?.errors?.match?.[0]
          || 'Could not accept invite. Try again.';
        throw new Error(msg);
      }
      goToMatch(data.redirect);
      return;
    } catch (e) {
      if (button) {
        button.disabled = false;
        button.textContent = label;
      }
      root.querySelectorAll('[data-rpsv-invite-accept="1"] button').forEach((btn) => {
        btn.disabled = false;
      });
      showRpsvPopup(root, 'Invite failed', e?.message || 'Could not accept invite.', 4000);
    }
  };

  root.addEventListener('submit', (e) => {
    const form = e.target?.closest?.('form[data-rpsv-invite-accept="1"]');
    if (!form) return;
    e.preventDefault();
    acceptInvite(form, form.querySelector('button[type="submit"], button'));
  });

  const rememberIncoming = (id) => {
    shownIncoming.add(Number(id));
    sessionStorage.setItem(shownIncomingKey, JSON.stringify([...shownIncoming]));
  };

  const announceIncoming = (inv, { force = false } = {}) => {
    const id = Number(inv.id);
    if (!id || announcedThisPage.has(id)) return false;
    if (!force && shownIncoming.has(id)) return false;
    showRpsvPopup(
      root,
      'New invitation',
      `${inv.from || 'A player'} invited you for ${inv.label || 'a match'}.`,
      3500,
    );
    activateTab('mine');
    announcedThisPage.add(id);
    rememberIncoming(id);
    return true;
  };

  // Receiver already on lobby with pending invites — always announce once per page load
  const bootIncoming = readJsonScript('[data-rpsv-incoming-boot]');
  if (Array.isArray(bootIncoming) && bootIncoming.length) {
    for (const inv of bootIncoming) {
      if (announceIncoming(inv, { force: true })) break;
    }
  }

  const renderIncoming = (items, announceNew = false) => {
    const list = items || [];
    if (announceNew) {
      for (const inv of list) {
        if (announceIncoming(inv)) break;
      }
    }

    const html = list.map((inv) => `
      <div class="rpsv-invite-row">
        <div>
          <strong>${inv.from || 'Player'}</strong>
          ${inv.member_id ? `<em>${inv.member_id}</em>` : ''}
          <span>${inv.label || ''}</span>
        </div>
        <div class="rpsv-invite-actions">
          ${formRow(inv.accept_url, 'Accept', 'rpsv-btn-gold')}
          ${formRow(inv.decline_url, 'Decline', 'rpsv-btn-ghost')}
        </div>
      </div>
    `).join('');
    if (incomingList) incomingList.innerHTML = html;
    if (incomingMine) {
      incomingMine.innerHTML = html || '<p class="rpsv-empty" data-incoming-empty>No incoming invitations.</p>';
    }
    if (incomingWrap) incomingWrap.hidden = !list.length;
  };

  const renderOutgoing = (items) => {
    if (!outgoingList) return;
    if (!items || !items.length) {
      outgoingList.innerHTML = '<p class="rpsv-empty" data-outgoing-empty>No outgoing invitations.</p>';
      return;
    }
    outgoingList.innerHTML = items.map((inv) => `
      <div class="rpsv-invite-row">
        <div>
          <span>To ${inv.to || 'Player'}${inv.member_id ? ` (${inv.member_id})` : ''}</span>
          <span>${inv.label || ''}</span>
        </div>
        <div class="rpsv-invite-actions">
          <em>Pending</em>
          ${formRow(inv.cancel_url, 'Cancel', 'rpsv-btn-ghost')}
        </div>
      </div>
    `).join('');
  };

  const statusUrl = root.dataset.queueStatus;
  if (statusUrl) {
    const scheduleLobbyPoll = (ms) => {
      if (pollTimer) clearInterval(pollTimer);
      pollTimer = setInterval(() => { poll(); }, Math.max(300, ms));
    };

    const poll = async () => {
      if (pollBusy || redirectingToMatch) return;
      pollBusy = true;
      try {
        const res = await fetch(statusUrl, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) return;
        const data = await res.json();
        if (data.incoming) renderIncoming(data.incoming, true);
        if (data.outgoing) renderOutgoing(data.outgoing);
        hasPendingInvites = (data.incoming?.length || 0) > 0 || (data.outgoing?.length || 0) > 0;
        scheduleLobbyPoll(hasPendingInvites ? 400 : 1500);
        if (typeof data.ngn_balance === 'number') {
          ngnBal = money('NGN', data.ngn_balance);
          const ngnNode = root.querySelector('[data-ngn-bal]');
          if (ngnNode) ngnNode.textContent = ngnBal;
        }
        if (Array.isArray(data.crypto_wallets) && data.crypto_wallets.length) {
          cryptoWallets = cryptoWallets.map((local) => {
            const fresh = data.crypto_wallets.find((w) => w.code === local.code);
            return fresh ? { ...local, ...fresh } : local;
          });
        }
        syncWalletUi();
        const wallet = currentWallet();
        refreshHeaderBalance(wallet.formatted || (category === 'fiat' ? ngnBal : money('USD', data.usd_balance || 0)));
        if (typeof data.unread_notifications === 'number') {
          refreshNotifBadge(data.unread_notifications);
        }
        if (data.status === 'matched' && data.redirect) {
          goToMatch(data.redirect);
        }
      } catch (e) {
        // ignore transient poll errors
      } finally {
        pollBusy = false;
      }
    };
    poll();
    scheduleLobbyPoll(800);
  }
}

function initMatch() {
  const root = document.getElementById('rpsv-match');
  if (!root || root.dataset.rpsvMatchReady === '1') return;
  root.dataset.rpsvMatchReady = '1';

  const stateUrl = root.dataset.stateUrl;
  const moveUrl = root.dataset.moveUrl;
  const readyUrl = root.dataset.readyUrl;
  const csrf = root.dataset.csrf;
  const moveImgs = {
    rock: root.dataset.rock,
    paper: root.dataset.paper,
    scissors: root.dataset.scissors,
  };
  let ended = false;
  let lastToastKey = '';
  let localSeconds = 0;
  let localPhase = '';
  let timerTick = null;
  let lastState = null;
  let moveBusy = false;
  let pollMs = 250;
  let pollTimer = null;
  let pollInFlight = false;

  const fmtMoney = (s, v) => {
    const code = String(s.currency || '').toUpperCase();
    const n = Number(v || 0);
    if (code === 'NGN') return `₦${n.toFixed(2)}`;
    if (['USD', 'USDT', 'USDC'].includes(code)) return `$${n.toFixed(2)}`;
    const decimals = n >= 1 ? 4 : 8;
    return `${n.toFixed(decimals).replace(/\.?0+$/, '')} ${code || ''}`.trim();
  };

  const paintTimer = () => {
    const el = root.querySelector('[data-timer]');
    if (!el) return;
    if (localPhase === 'idle' || localPhase === 'select' || localPhase === 'ready') {
      el.textContent = '--';
      return;
    }
    el.textContent = String(Math.max(0, Math.floor(localSeconds))).padStart(2, '0');
  };

  const schedulePoll = (ms) => {
    const next = Math.max(200, ms);
    if (next === pollMs && pollTimer) return;
    pollMs = next;
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => { poll(); }, pollMs);
  };

  const pollIntervalFor = (phase) => {
    if (phase === 'idle' || phase === 'select') return 250;
    if (phase === 'ready' || phase === 'reveal' || phase === 'result') return 350;
    return 800;
  };

  const syncLocalTimer = (s) => {
    const nextPhase = s.phase || '';
    const nextSeconds = Number(s.seconds_left || 0);
    const phaseChanged = localPhase !== nextPhase;
    localPhase = nextPhase;

    // No choice countdown — only tick brief reveal/result windows.
    if (localPhase === 'idle' || localPhase === 'select' || localPhase === 'ready') {
      localSeconds = 0;
      if (timerTick) {
        clearInterval(timerTick);
        timerTick = null;
      }
      paintTimer();
      return;
    }

    if (phaseChanged || Math.abs(nextSeconds - localSeconds) > 1.25 || nextSeconds > localSeconds + 0.5) {
      localSeconds = nextSeconds;
    }
    paintTimer();

    if (timerTick) {
      clearInterval(timerTick);
      timerTick = null;
    }
    if ((localPhase === 'reveal' || localPhase === 'result') && localSeconds > 0) {
      timerTick = setInterval(() => {
        if (localSeconds <= 0) {
          clearInterval(timerTick);
          timerTick = null;
          poll(true);
          return;
        }
        localSeconds -= 1;
        paintTimer();
        if (localSeconds <= 0) {
          clearInterval(timerTick);
          timerTick = null;
          poll(true);
        }
      }, 1000);
    }
  };

  const startSelectTimerOptimistic = () => {
    localPhase = 'select';
    localSeconds = 0;
    paintTimer();
    const caption = root.querySelector('[data-timer-caption]');
    if (caption) caption.textContent = 'WAIT';
    const phaseEl = root.querySelector('[data-phase-label]');
    if (phaseEl) phaseEl.textContent = 'WAITING FOR PLAYER 2';
  };

  const showToast = (toast) => {
    if (!toast || !toast.title) return;
    const key = `${toast.round || ''}:${toast.kind}:${toast.title}`;
    if (key === lastToastKey) return;
    lastToastKey = key;
    let el = root.querySelector('[data-rpsv-toast]');
    if (!el) el = document.querySelector('[data-rpsv-toast]');
    if (!el) return;
    if (el.parentElement !== document.body) {
      document.body.appendChild(el);
    }
    el.dataset.kind = toast.kind || 'info';
    const title = el.querySelector('[data-toast-title]');
    const message = el.querySelector('[data-toast-message]');
    const icon = el.querySelector('[data-toast-icon]');
    if (title) title.textContent = toast.title;
    if (message) message.textContent = toast.message || '';
    if (icon) {
      icon.textContent = toast.kind === 'win' ? '✓' : toast.kind === 'loss' ? '✕' : '=';
    }
    el.hidden = false;
    window.clearTimeout(showToast._timer);
    const hold = toast.round && String(toast.round).startsWith('match:') ? 4500 : 3000;
    showToast._timer = window.setTimeout(() => { el.hidden = true; }, hold);
  };

  const render = (s) => {
    lastState = s;
    const setText = (sel, val) => {
      const el = root.querySelector(sel);
      if (el) el.textContent = val;
    };

    const history = s.history || [];
    const youWins = history.filter((h) => h.you_won).length;
    const oppWins = history.filter((h) => h.winner_id && !h.you_won).length;
    const played = Math.max(1, history.filter((h) => h.winner_id || h.draw).length);
    const youRate = Math.round((youWins / played) * 100);
    const oppRate = Math.round((oppWins / played) * 100);
    const oppName = s.opponent?.name || 'Opponent';
    const oppLocked = !!(s.opponent?.locked);

    setText('[data-rf-stake]', fmtMoney(s, s.stake));
    setText('[data-rf-pool]', fmtMoney(s, s.prize_pool));
    setText('[data-rf-fee]', fmtMoney(s, s.platform_fee));
    setText('[data-rf-win]', fmtMoney(s, s.winner_payout));
    setText('[data-sum-stake]', fmtMoney(s, s.stake));
    setText('[data-sum-pool]', fmtMoney(s, s.prize_pool));
    setText('[data-sum-fee]', `5% · ${fmtMoney(s, s.platform_fee)}`);
    setText('[data-sum-win]', fmtMoney(s, s.winner_payout));
    setText('[data-rr-label]', `ROUND ${s.current_round} · FIRST TO ${s.wins_needed || 2}`);
    setText('[data-you-name]', s.you.name || 'You');
    setText('[data-opp-name]', oppName);
    setText('[data-you-score]', s.you.score);
    setText('[data-opp-score]', s.opponent.score);
    setText('[data-you-rate]', `${youRate}%`);
    setText('[data-opp-rate]', `${oppRate}%`);
    syncLocalTimer(s);

    let phaseLabel = String(s.phase || '').toUpperCase();
    if (s.turn_prompt) {
      phaseLabel = s.your_turn ? 'YOUR TURN' : 'WAITING';
    } else if (s.phase === 'ready') phaseLabel = 'PRESS START';
    else if (s.phase === 'reveal') phaseLabel = 'REVEALING MOVES';
    else if (s.phase === 'result') phaseLabel = 'ROUND RESULT';

    setText('[data-phase-label]', phaseLabel);
    setText('[data-status-label]', String(s.status || '').toUpperCase());
    setText('[data-your-move-state]', s.you.move ? `(Locked: ${String(s.you.move).toUpperCase()})` : (s.your_turn ? '(Choose)' : '(Wait)'));
    if (!s.opponent.hidden && s.opponent.move) {
      setText('[data-opp-move-state]', `(${String(s.opponent.move).toUpperCase()})`);
    } else if (oppLocked && (s.phase === 'select' || s.phase === 'idle')) {
      setText('[data-opp-move-state]', '(Locked)');
    } else if (!s.opponent.hidden && !s.opponent.move) {
      setText('[data-opp-move-state]', '(No move)');
    } else {
      setText('[data-opp-move-state]', '(Hidden)');
    }

    const caption = root.querySelector('[data-timer-caption]');
    if (caption) {
      caption.textContent = s.phase === 'ready'
        ? 'READY'
        : (s.phase === 'idle' || s.phase === 'select')
          ? (s.your_turn ? 'YOUR TURN' : 'WAIT')
          : (s.phase === 'result' ? 'NEXT' : 'SECONDS');
    }

    const turnHint = root.querySelector('[data-turn-prompt]');
    if (turnHint) {
      turnHint.textContent = s.turn_prompt || '';
      turnHint.hidden = !s.turn_prompt;
    }

    const readyOverlay = root.querySelector('[data-ready-overlay]');
    if (readyOverlay) {
      readyOverlay.hidden = s.phase !== 'ready';
      const title = readyOverlay.querySelector('[data-ready-title]');
      const sub = readyOverlay.querySelector('[data-ready-sub]');
      const btn = readyOverlay.querySelector('[data-ready-btn]');
      const oppReady = !!(s.opponent_ready || s.opponent?.ready);
      if (s.you_ready) {
        if (title) title.textContent = 'Waiting for opponent…';
        if (sub) sub.textContent = `${oppName} has not pressed Start yet.`;
        if (btn) {
          btn.disabled = true;
          btn.textContent = 'You are ready';
        }
      } else if (oppReady) {
        if (title) title.textContent = `${oppName} is waiting for you`;
        if (sub) sub.textContent = `${oppName} has pressed Start. Press Start to begin.`;
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'Start Game';
        }
      } else {
        if (title) title.textContent = 'Both players must press Start';
        if (sub) sub.textContent = 'Game will not begin until both of you are ready.';
        if (btn) {
          btn.disabled = false;
          btn.textContent = 'Start Game';
        }
      }
    }

    const banner = root.querySelector('[data-round-banner]');
    if (banner) {
      if (s.banner && s.banner.title) {
        banner.hidden = false;
        banner.dataset.kind = s.banner.kind || 'info';
        const kicker = banner.querySelector('[data-banner-kicker]');
        if (kicker) {
          kicker.textContent = (s.banner.kind === 'turn' || s.phase === 'idle' || s.phase === 'select')
            ? 'Your turn'
            : s.phase === 'reveal'
              ? 'Revealing'
              : 'Round result';
        }
        const title = banner.querySelector('[data-banner-title]');
        const sub = banner.querySelector('[data-banner-sub]');
        if (title) title.textContent = s.banner.title;
        if (sub) sub.textContent = s.banner.subtitle || '';
      } else {
        banner.hidden = true;
      }
    }

    const dots = root.querySelector('[data-rr-dots]');
    if (dots) {
      const needed = Math.max(2, Number(s.wins_needed) || 2);
      const youScore = Number(s.you?.score || 0);
      dots.innerHTML = '';
      for (let i = 1; i <= needed; i++) {
        const el = document.createElement('i');
        if (i <= youScore) el.className = 'is-done';
        else if (i === youScore + 1) el.className = 'is-active';
        dots.appendChild(el);
      }
    }

    const canMove = !!s.can_move;
    root.querySelectorAll('[data-move]').forEach((btn) => {
      btn.disabled = !canMove;
      btn.classList.toggle('is-picked', s.you.move === btn.dataset.move);
      btn.classList.toggle('is-round-win', !s.opponent.hidden && s.round_outcome === 'win' && s.you.move === btn.dataset.move);
    });

    const oppSlot = root.querySelector('[data-opp-slot]');
    if (oppSlot) {
      const keys = ['rock', 'paper', 'scissors'];
      oppSlot.innerHTML = keys.map((move) => {
        const revealed = !s.opponent.hidden && s.opponent.move === move;
        const won = revealed && s.round_outcome === 'loss';
        if (s.opponent.hidden || !s.opponent.move) {
          const lockedHint = oppLocked && s.phase === 'select'
            ? '<span class="rm-locked">LOCKED</span>'
            : '<span class="rm-q">?</span>';
          return `<div class="rm-move is-opp ${oppLocked && s.phase === 'select' ? 'is-opp-locked' : ''}"><img src="${moveImgs[move]}" alt="" class="rm-move-ghost">${lockedHint}<strong>${move.toUpperCase()}</strong></div>`;
        }
        return `<div class="rm-move is-opp ${revealed ? 'is-revealed' : ''} ${won ? 'is-round-win' : ''}"><img src="${moveImgs[move]}" alt="${move}"><strong>${move.toUpperCase()}</strong></div>`;
      }).join('');
    }

    const hist = root.querySelector('[data-history]');
    if (hist) {
      const byRound = {};
      (s.history || []).forEach((h) => { byRound[h.round] = h; });
      const total = Math.max(Number(s.current_round) || 1, Object.keys(byRound).length, 1);
      hist.innerHTML = '';
      for (let i = 1; i <= total; i++) {
        const h = byRound[i];
        const div = document.createElement('div');
        const isCurrent = i === Number(s.current_round) && !h;
        div.className = 'rm-hist' + (h ? '' : ' is-empty') + (isCurrent ? ' is-current' : '') + (h && i === Number(s.current_round) ? ' is-live' : '');
        if (!h) {
          div.innerHTML = `<em>R${i}</em><span class="rm-hist-wait">Waiting…</span>`;
        } else {
          let result;
          let cls = '';
          if (h.you_won) {
            result = h.timeout ? '<i class="fa-solid fa-check"></i> Win (timeout)' : '<i class="fa-solid fa-check"></i> You Win';
            cls = 'is-win';
          } else if (h.winner_id) {
            result = h.timeout ? '<i class="fa-solid fa-xmark"></i> Loss (timeout)' : '<i class="fa-solid fa-xmark"></i> You Lost';
            cls = 'is-loss';
          } else {
            result = 'Draw';
            cls = 'is-draw';
          }
          const youCell = h.you
            ? `<img src="${moveImgs[h.you] || moveImgs.rock}" alt="${h.you}">`
            : '<span class="rm-hist-none">—</span>';
          const oppCell = h.opponent
            ? `<img src="${moveImgs[h.opponent] || moveImgs.rock}" alt="${h.opponent}">`
            : '<span class="rm-hist-none">—</span>';
          div.innerHTML = `<em>R${i}</em>${youCell}<span>vs</span>${oppCell}<b class="${cls}">${result}</b>`;
        }
        hist.appendChild(div);
      }
    }

    if (s.toast) showToast(s.toast);
    else if (s.match_result) showToast(s.match_result);

    if (typeof s.wallet === 'number') {
      refreshHeaderBalance(fmtMoney(s, s.wallet));
    }
    if (typeof s.usd_balance === 'number' || typeof s.ngn_balance === 'number') {
      const bal = s.currency === 'NGN' ? s.ngn_balance : s.usd_balance;
      if (typeof bal === 'number') refreshHeaderBalance(fmtMoney(s, bal));
    }
    if (typeof s.unread_notifications === 'number') {
      refreshNotifBadge(s.unread_notifications);
    }

    if (['completed', 'cancelled', 'forfeit'].includes(s.status) && !ended) {
      ended = true;
      const endBanner = root.querySelector('[data-end-banner]');
      const text = root.querySelector('[data-end-text]');
      if (endBanner && text) {
        endBanner.hidden = false;
        const mr = s.match_result;
        if (mr?.title) {
          text.textContent = `${mr.title}. ${mr.message || ''}`.trim();
        } else if (s.winner_id && s.winner_id === s.you.id) {
          text.textContent = `You won ${fmtMoney(s, s.winner_payout)}!`;
        } else if (s.winner_id) {
          text.textContent = `You lost this match. Stake ${fmtMoney(s, s.stake)} was taken.`;
        } else {
          text.textContent = `Match draw. Stake ${fmtMoney(s, s.stake)} refunded.`;
        }
      }
      if (s.match_result) showToast(s.match_result);
    }
  };

  root.querySelector('[data-ready-btn]')?.addEventListener('click', async () => {
    if (!readyUrl) return;
    try {
      const res = await fetch(readyUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
      });
      const data = await res.json();
      if (res.ok) {
        render(data);
        schedulePoll(pollIntervalFor(data.phase));
      }
    } catch (e) {}
  });

  root.querySelectorAll('[data-move]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      if (moveBusy || btn.disabled || !moveUrl) return;
      const move = btn.dataset.move;
      const wasIdle = lastState?.phase === 'idle';
      moveBusy = true;
      root.querySelectorAll('[data-move]').forEach((b) => {
        b.disabled = true;
        b.classList.toggle('is-picked', b.dataset.move === move);
      });
      const youState = root.querySelector('[data-your-move-state]');
      if (youState) youState.textContent = `(Locked: ${String(move).toUpperCase()})`;
      if (wasIdle) startSelectTimerOptimistic();

      try {
        const res = await fetch(moveUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify({ move }),
        });
        const data = await res.json();
        if (res.ok) {
          render(data);
          schedulePoll(pollIntervalFor(data.phase));
          // Pull again immediately so opponent-facing flags stay fresh
          setTimeout(() => poll(true), 120);
        } else {
          await poll(true);
        }
      } catch (e) {
        await poll(true);
      } finally {
        moveBusy = false;
      }
    });
  });

  const poll = async (force = false) => {
    if (pollInFlight && !force) return;
    pollInFlight = true;
    try {
      const res = await fetch(stateUrl, {
        headers: { Accept: 'application/json', 'Cache-Control': 'no-cache' },
        cache: 'no-store',
      });
      if (!res.ok) return;
      const data = await res.json();
      render(data);
      schedulePoll(pollIntervalFor(data.phase));
    } catch (e) {
    } finally {
      pollInFlight = false;
    }
  };

  poll(true);
  schedulePoll(250);
}

function bootRpsvUi() {
  initLobby();
  initMatch();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootRpsvUi);
} else {
  bootRpsvUi();
}