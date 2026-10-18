@php
    $tone = $msg['tone'] ?? 'gold';
    $isAdmin = !empty($msg['is_admin']);
    $isPinned = !empty($msg['is_pinned']);
    $reactions = $msg['reactions'] ?? [];
    $myReaction = $msg['my_reaction'] ?? null;
    $reactionEmojis = \App\Services\ChatService::REACTION_EMOJIS;
    $username = $msg['user'] ?? 'User';
@endphp
<article
    class="ocg-msg {{ $isAdmin ? 'is-admin' : '' }} {{ $isPinned ? 'is-pinned-msg' : '' }} ocg-msg--{{ $tone }} {{ !empty($msg['mine']) ? 'is-mine' : '' }}"
    data-id="{{ $msg['id'] }}"
    data-ocg-msg
>
    <div class="ocg-msg-avatar {{ $isAdmin ? 'is-admin' : '' }}">
        @if (!empty($msg['avatar']))
            <img src="{{ $msg['avatar'] }}" alt="{{ $username }}" class="ocg-avatar-img" loading="lazy">
        @elseif ($isAdmin)
            <img src="{{ asset('images/chat/avatar_gamemaster.png') }}" alt="GameMaster" class="ocg-avatar-img">
        @else
            <i class="fa-solid fa-user"></i>
        @endif
    </div>

    <div class="ocg-msg-body">
        <header class="ocg-msg-head">
            <div class="ocg-msg-user-meta">
                <strong class="ocg-msg-name">{{ !empty($msg['mine']) ? 'You' : $username }}</strong>
                @if ($isAdmin)
                    <span class="ocg-admin-badge">ADMIN</span>
                @elseif (stripos($username, 'NaijaBoss') !== false)
                    <span class="ocg-crown-icon" aria-label="VIP">&#x1F451;</span>
                @elseif (!empty($msg['badge']))
                    <span class="ocg-vip is-vip-{{ $msg['vip'] ?? 1 }}">{{ $msg['badge'] }}</span>
                @endif
            </div>

            <div class="ocg-msg-meta-right">
                @if ($isPinned)
                    <span class="ocg-pinned-pin-icon" title="Pinned message">&#x1F4CC;</span>
                @endif
                <time class="ocg-msg-time">{{ $msg['at'] ?? '' }}</time>
            </div>
        </header>

        <p class="ocg-msg-text">{!! nl2br(e($msg['body'])) !!}</p>

        @if (!empty($reactions))
            <footer class="ocg-msg-foot">
                <div class="ocg-reacts" data-ocg-reacts>
                    @foreach ($reactions as $emoji => $count)
                        <button
                            type="button"
                            class="ocg-react {{ $myReaction === $emoji ? 'is-mine' : '' }}"
                            data-ocg-react
                            data-id="{{ $msg['id'] }}"
                            data-emoji="{{ $emoji }}"
                            aria-label="React {{ $emoji }}"
                        >
                            <span class="ocg-react-emoji">{{ $emoji }}</span>
                            <span class="ocg-react-count">{{ (int) $count }}</span>
                        </button>
                    @endforeach
                    <div class="ocg-react-wrap">
                        <button type="button" class="ocg-react-add" data-ocg-react-open data-id="{{ $msg['id'] }}" aria-label="Add reaction">
                            <i class="fa-regular fa-face-smile"></i>+
                        </button>
                        <div class="ocg-react-picker" data-ocg-react-picker hidden>
                            @foreach ($reactionEmojis as $emoji)
                                <button type="button" data-ocg-react data-id="{{ $msg['id'] }}" data-emoji="{{ $emoji }}" aria-label="{{ $emoji }}">{{ $emoji }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </footer>
        @endif
    </div>
</article>
