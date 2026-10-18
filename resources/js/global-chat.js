document.addEventListener('DOMContentLoaded', () => {
    initGlobalChat();
});

function initGlobalChat() {
    const root = document.querySelector('[data-oc-gchat]');
    if (!root) return;

    const stream = root.querySelector('[data-ocg-stream]');
    const form = root.querySelector('[data-ocg-form]');
    const input = root.querySelector('[data-ocg-input]');
    const err = root.querySelector('[data-ocg-error]');
    const onlineEl = root.querySelector('[data-ocg-online]');
    const pin = root.querySelector('[data-ocg-pin]');
    const pinBody = root.querySelector('[data-ocg-pin-body]');
    const emojiPanel = root.querySelector('[data-ocg-emoji-panel]');
    const emojiGrid = root.querySelector('[data-ocg-emoji-grid]');

    const messagesUrl = root.dataset.messagesUrl;
    const sendUrl = root.dataset.sendUrl;
    const reactBase = root.dataset.reactUrl;
    const csrf = root.dataset.csrf;
    const channel = root.dataset.channel || 'global';
    let canPost = root.dataset.canPost === '1';

    let reactionEmojis = [];
    let composerEmojis = [];
    try {
        reactionEmojis = JSON.parse(root.dataset.reactionEmojis || '[]');
    } catch (_) {
        reactionEmojis = ['👍', '❤️', '🔥', '😂', '🎉', '👏'];
    }
    try {
        composerEmojis = JSON.parse(root.dataset.composerEmojis || '[]');
    } catch (_) {
        composerEmojis = reactionEmojis;
    }

    let lastId = [...stream.querySelectorAll('[data-id]')].reduce(
        (max, el) => Math.max(max, Number(el.dataset.id || 0)),
        0
    );

    const esc = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    const avatarHtml = (msg) => {
        if (msg.avatar) {
            return `<img src="${esc(msg.avatar)}" alt="${esc(msg.user || '')}" class="ocg-avatar-img">`;
        }
        if (msg.is_admin) {
            return `<img src="/images/chat/avatar_gamemaster.png" alt="GameMaster" class="ocg-avatar-img">`;
        }
        return '<i class="fa-solid fa-user"></i>';
    };

    const reactionsHtml = (msg) => {
        const reactions = msg.reactions || {};
        const my = msg.my_reaction || null;
        const chips = Object.entries(reactions)
            .filter(([, count]) => Number(count) > 0)
            .map(([emoji, count]) => `
                <button type="button" class="ocg-react ${my === emoji ? 'is-mine' : ''}" data-ocg-react data-id="${msg.id}" data-emoji="${esc(emoji)}" aria-label="React ${esc(emoji)}">
                    <span class="ocg-react-emoji">${esc(emoji)}</span>
                    <span class="ocg-react-count">${Number(count)}</span>
                </button>
            `)
            .join('');

        const picker = reactionEmojis
            .map((emoji) => `<button type="button" data-ocg-react data-id="${msg.id}" data-emoji="${esc(emoji)}" aria-label="${esc(emoji)}">${esc(emoji)}</button>`)
            .join('');

        return `
            <footer class="ocg-msg-foot">
                <div class="ocg-reacts" data-ocg-reacts>
                    ${chips}
                    <div class="ocg-react-wrap">
                        <button type="button" class="ocg-react-add" data-ocg-react-open data-id="${msg.id}" aria-label="Add reaction">
                            <i class="fa-regular fa-face-smile"></i>+
                        </button>
                        <div class="ocg-react-picker" data-ocg-react-picker hidden>${picker}</div>
                    </div>
                </div>
            </footer>
        `;
    };

    const updateReaction = (msg) => {
        const card = stream.querySelector(`[data-id="${msg.id}"]`);
        if (!card) return;
        let foot = card.querySelector('.ocg-msg-foot');
        if (foot) {
            foot.outerHTML = reactionsHtml(msg);
        } else {
            const body = card.querySelector('.ocg-msg-body');
            if (body) {
                body.insertAdjacentHTML('beforeend', reactionsHtml(msg));
            }
        }
    };

    const renderMessage = (msg) => {
        if (stream.querySelector(`[data-id="${msg.id}"]`)) {
            return updateReaction(msg);
        }

        stream.querySelector('[data-ocg-empty]')?.remove();

        const tone = msg.tone || 'gold';
        const isAdmin = !!msg.is_admin;
        const isPinned = !!msg.is_pinned;
        const article = document.createElement('article');
        article.className = `ocg-msg ${isAdmin ? 'is-admin' : ''} ${isPinned ? 'is-pinned-msg' : ''} ocg-msg--${tone} ${msg.mine ? 'is-mine' : ''}`;
        article.dataset.id = String(msg.id);
        article.dataset.ocgMsg = '';

        let badge = '';
        if (isAdmin) {
            badge = '<span class="ocg-admin-badge">ADMIN</span>';
        } else if ((msg.user || '').toLowerCase().includes('naijaboss')) {
            badge = '<span class="ocg-crown-icon" aria-label="VIP">&#x1F451;</span>';
        } else if (msg.badge) {
            badge = `<span class="ocg-vip is-vip-${msg.vip || 1}">${esc(msg.badge)}</span>`;
        }

        const pinIcon = isPinned ? '<span class="ocg-pinned-pin-icon" title="Pinned message">&#x1F4CC;</span>' : '';
        const hasReactions = msg.reactions && Object.keys(msg.reactions).length > 0;

        article.innerHTML = `
            <div class="ocg-msg-avatar ${isAdmin ? 'is-admin' : ''}">${avatarHtml(msg)}</div>
            <div class="ocg-msg-body">
                <header class="ocg-msg-head">
                    <div class="ocg-msg-user-meta">
                        <strong class="ocg-msg-name">${esc(msg.mine ? 'You' : msg.user)}</strong>
                        ${badge}
                    </div>
                    <div class="ocg-msg-meta-right">
                        ${pinIcon}
                        <time class="ocg-msg-time">${esc(msg.at || '')}</time>
                    </div>
                </header>
                <p class="ocg-msg-text"></p>
                ${hasReactions ? reactionsHtml(msg) : ''}
            </div>
        `;
        article.querySelector('.ocg-msg-text').textContent = msg.body || '';
        stream.appendChild(article);
        stream.scrollTop = stream.scrollHeight;
        lastId = Math.max(lastId, Number(msg.id) || 0);
    };

    const setPinned = (pinned) => {
        if (!pin) return;
        if (!pinned || !pinned.body) {
            pin.hidden = true;
            pin.classList.add('is-empty');
            return;
        }
        pin.hidden = false;
        pin.classList.remove('is-empty');
        if (pinBody) pinBody.textContent = pinned.body;
    };

    const setOnline = (count) => {
        if (onlineEl && count != null) {
            onlineEl.textContent = Number(count).toLocaleString();
        }
    };

    const scrollToBottom = (smooth = true) => {
        if (!stream) return;
        requestAnimationFrame(() => {
            if (smooth) {
                stream.scrollTo({
                    top: stream.scrollHeight,
                    behavior: 'smooth',
                });
            } else {
                stream.scrollTop = stream.scrollHeight;
            }
        });
    };

    // Scroll to bottom on initial load
    setTimeout(() => scrollToBottom(false), 100);
    setTimeout(() => scrollToBottom(false), 300);

    // Emoji panel toggle
    if (emojiGrid && composerEmojis.length) {
        emojiGrid.innerHTML = composerEmojis
            .map((e) => `<button type="button" class="ocg-emoji-tile" data-emoji="${e}">${e}</button>`)
            .join('');
    }

    const toggleEmojiPanel = () => {
        if (!emojiPanel) return;
        emojiPanel.hidden = !emojiPanel.hidden;
    };

    root.querySelectorAll('[data-ocg-composer-emoji]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleEmojiPanel();
        });
    });

    emojiGrid?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-emoji]');
        if (!btn || !input) return;
        input.value += btn.dataset.emoji;
        input.focus();
    });

    document.addEventListener('click', (e) => {
        if (emojiPanel && !emojiPanel.hidden && !e.target.closest('[data-ocg-emoji-panel]') && !e.target.closest('[data-ocg-composer-emoji]')) {
            emojiPanel.hidden = true;
        }
    });

    // Send message via AJAX
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!canPost || !input) return;
        const val = input.value.trim();
        if (!val) return;

        if (err) err.hidden = true;
        input.disabled = true;

        try {
            const res = await fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    channel,
                    body: val,
                }),
            });

            const data = await res.json();
            if (!res.ok) {
                const msg = data.message || (data.errors ? Object.values(data.errors).flat()[0] : 'Failed to send.');
                throw new Error(msg);
            }

            input.value = '';
            if (emojiPanel) emojiPanel.hidden = true;
            renderMessage(data.message);
            scrollToBottom(true);
        } catch (errObj) {
            if (err) {
                err.textContent = errObj.message;
                err.hidden = false;
            }
        } finally {
            input.disabled = false;
            input.focus();
        }
    });

    // Handle reaction clicks & picker
    stream?.addEventListener('click', async (e) => {
        const addBtn = e.target.closest('[data-ocg-react-open]');
        if (addBtn) {
            e.stopPropagation();
            const wrap = addBtn.closest('.ocg-react-wrap');
            const picker = wrap?.querySelector('[data-ocg-react-picker]');
            if (picker) {
                document.querySelectorAll('[data-ocg-react-picker]:not([hidden])').forEach((p) => {
                    if (p !== picker) p.hidden = true;
                });
                picker.hidden = !picker.hidden;
            }
            return;
        }

        const btn = e.target.closest('[data-ocg-react]');
        if (!btn) return;
        e.preventDefault();

        const id = btn.dataset.id;
        const emoji = btn.dataset.emoji;
        if (!id || !emoji) return;

        const picker = btn.closest('[data-ocg-react-picker]');
        if (picker) picker.hidden = true;

        btn.disabled = true;
        try {
            const res = await fetch(`${reactBase}/${id}/react`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ emoji }),
            });
            const data = await res.json();
            if (res.ok && data.message) {
                updateReaction(data.message);
            }
        } catch (_) {
            // ignore network failure
        } finally {
            btn.disabled = false;
        }
    });

    // Close reaction picker on outside click
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.ocg-react-wrap')) {
            document.querySelectorAll('[data-ocg-react-picker]:not([hidden])').forEach((p) => {
                p.hidden = true;
            });
        }
    });

    // Polling new messages
    let polling = true;
    const poll = async () => {
        if (!polling) return;
        try {
            const url = `${messagesUrl}?channel=${encodeURIComponent(channel)}&after=${lastId}`;
            const res = await fetch(url, {
                headers: { Accept: 'application/json' },
            });
            if (res.ok) {
                const data = await res.json();
                if (Array.isArray(data.messages) && data.messages.length) {
                    const wasNearBottom = stream.scrollHeight - stream.scrollTop - stream.clientHeight < 120;
                    data.messages.forEach(renderMessage);
                    if (wasNearBottom) scrollToBottom(true);
                }
                if (data.online != null) setOnline(data.online);
                if (data.pinned) setPinned(data.pinned);
            }
        } catch (_) {
            // back off
        } finally {
            setTimeout(poll, 4000);
        }
    };

    setTimeout(poll, 4000);
}
