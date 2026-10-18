@php
    $gameChatRoom = $gameChatRoom ?? 'prediction';
    $chatRoom = app(\App\Services\ChatService::class)->roomFor($gameChatRoom);
    $chatMessages = app(\App\Services\ChatService::class)->latestMessages($chatRoom);
@endphp
<aside class="oc-game-chat" data-oc-chat data-room="{{ $gameChatRoom }}" data-messages-url="{{ route('user.chat.messages') }}" data-send-url="{{ route('user.chat.store') }}" data-csrf="{{ csrf_token() }}">
    <div class="oc-game-chat-head">
        <strong><i class="fa-solid fa-comments"></i> {{ $chatRoom->name }}</strong>
        <span>Talk with players in this game</span>
    </div>
    <div class="oc-game-chat-stream" data-oc-chat-stream>
        @foreach($chatMessages as $message)
            <div class="oc-game-chat-bubble {{ $message->user_id === auth()->id() ? 'is-mine' : '' }}" data-id="{{ $message->id }}">
                <strong>{{ $message->user?->name ?? 'User' }}</strong>
                <p>{{ $message->body }}</p>
                <small>{{ $message->created_at->format('H:i') }}</small>
            </div>
        @endforeach
    </div>
    <form class="oc-game-chat-form" data-oc-chat-form>
        <input type="text" name="body" maxlength="500" placeholder="Type a message..." required autocomplete="off">
        <button type="submit">Send</button>
    </form>
    <p class="oc-game-chat-error" data-oc-chat-error hidden></p>
</aside>

@once
@push('styles')
<style>
.oc-game-chat{margin-top:1rem;border:1px solid rgba(255,214,0,.28);border-radius:1rem;background:linear-gradient(180deg,#141414,#080808);overflow:hidden;display:flex;flex-direction:column;min-height:280px}
.oc-game-chat-head{padding:.75rem 1rem;border-bottom:1px solid rgba(255,255,255,.08)}
.oc-game-chat-head strong{display:block;color:#ffd600;font-size:.85rem}
.oc-game-chat-head span{color:#94a3b8;font-size:.72rem}
.oc-game-chat-stream{flex:1;max-height:220px;overflow:auto;padding:.75rem;display:flex;flex-direction:column;gap:.5rem;scrollbar-width:thin;scrollbar-color:rgba(255,214,0,.4) #0a0a0a}
.oc-game-chat-stream::-webkit-scrollbar{width:8px}
.oc-game-chat-stream::-webkit-scrollbar-track{background:#0a0a0a;border-radius:999px}
.oc-game-chat-stream::-webkit-scrollbar-thumb{background:rgba(255,214,0,.4);border-radius:999px;border:2px solid #0a0a0a}
.oc-game-chat-stream::-webkit-scrollbar-thumb:hover{background:rgba(255,214,0,.65)}
.oc-game-chat-stream::-webkit-scrollbar-corner{background:#0a0a0a}
.oc-game-chat-bubble{max-width:92%;padding:.5rem .65rem;border-radius:.75rem;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06)}
.oc-game-chat-bubble.is-mine{align-self:flex-end;background:rgba(255,214,0,.1);border-color:rgba(255,214,0,.25)}
.oc-game-chat-bubble strong{display:block;font-size:.68rem;color:#ffd600;margin-bottom:.15rem}
.oc-game-chat-bubble p{margin:0;color:#fff;font-size:.82rem;word-break:break-word}
.oc-game-chat-bubble small{color:#64748b;font-size:.65rem}
.oc-game-chat-form{display:flex;gap:.4rem;padding:.65rem;border-top:1px solid rgba(255,255,255,.08)}
.oc-game-chat-form input{flex:1;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:#0a0a0a;color:#fff;padding:.55rem .85rem;font-size:.82rem}
.oc-game-chat-form button{border:0;border-radius:999px;background:#ffd600;color:#111;font-weight:800;padding:.55rem .9rem;cursor:pointer}
.oc-game-chat-error{margin:0;padding:0 .65rem .65rem;color:#f87171;font-size:.75rem}
</style>
@endpush
@push('scripts')
<script>
(() => {
  document.querySelectorAll('[data-oc-chat]').forEach((root) => {
    if (root.dataset.bound === '1') return;
    root.dataset.bound = '1';
    const stream = root.querySelector('[data-oc-chat-stream]');
    const form = root.querySelector('[data-oc-chat-form]');
    const err = root.querySelector('[data-oc-chat-error]');
    const room = root.dataset.room || 'prediction';
    const messagesUrl = root.dataset.messagesUrl;
    const sendUrl = root.dataset.sendUrl;
    const csrf = root.dataset.csrf;
    let lastId = [...stream.querySelectorAll('[data-id]')].reduce((m, el) => Math.max(m, Number(el.dataset.id || 0)), 0);

    const append = (msg) => {
      if (stream.querySelector(`[data-id="${msg.id}"]`)) return;
      const div = document.createElement('div');
      div.className = 'oc-game-chat-bubble' + (msg.mine ? ' is-mine' : '');
      div.dataset.id = msg.id;
      div.innerHTML = `<strong></strong><p></p><small></small>`;
      div.querySelector('strong').textContent = msg.user;
      div.querySelector('p').textContent = msg.body;
      div.querySelector('small').textContent = msg.at || '';
      stream.appendChild(div);
      stream.scrollTop = stream.scrollHeight;
      lastId = Math.max(lastId, msg.id);
    };

    stream.scrollTop = stream.scrollHeight;

    const poll = async () => {
      try {
        const res = await fetch(`${messagesUrl}?room=${encodeURIComponent(room)}&after_id=${lastId}`, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) return;
        const data = await res.json();
        (data.messages || []).forEach(append);
      } catch (e) {}
    };

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      err.hidden = true;
      const input = form.querySelector('input[name="body"]');
      const body = input.value.trim();
      if (!body) return;
      try {
        const res = await fetch(sendUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
          body: JSON.stringify({ body, room })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          err.textContent = data?.errors?.body?.[0] || data?.message || 'Could not send.';
          err.hidden = false;
          return;
        }
        append(data.message);
        input.value = '';
      } catch (e) {
        err.textContent = 'Network error.';
        err.hidden = false;
      }
    });

    setInterval(poll, 4000);
  });
})();
</script>
@endpush
@endonce
