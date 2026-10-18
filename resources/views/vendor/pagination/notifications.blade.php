@if ($paginator->hasPages())
    <nav class="oc-notif-pager" role="navigation" aria-label="Notifications pagination">
        <p class="oc-notif-pager-info">
            Showing <strong>{{ $paginator->firstItem() }}</strong> to <strong>{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong> notifications
        </p>

        <div class="oc-notif-pager-nav">
            @if ($paginator->onFirstPage())
                <span class="oc-notif-pager-btn is-disabled" aria-disabled="true">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="oc-notif-pager-btn" rel="prev" aria-label="Previous page">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="oc-notif-pager-btn is-dots">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="oc-notif-pager-btn is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="oc-notif-pager-btn">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="oc-notif-pager-btn" rel="next" aria-label="Next page">
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            @else
                <span class="oc-notif-pager-btn is-disabled" aria-disabled="true">
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
