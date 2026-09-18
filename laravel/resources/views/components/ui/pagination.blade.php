{{-- Laravel's paginator, wrapped in .pagination so the AJAX tables can hook it. --}}
@if ($paginator->hasPages())
    <nav class="pagination flex flex-wrap items-center gap-1.5" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-disabled="true">&larr;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">&larr;</a>
        @endif

        @foreach (($elements ?? []) as $element)
            @if (is_string($element))
                <span class="is-disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">&rarr;</a>
        @else
            <span class="is-disabled" aria-disabled="true">&rarr;</span>
        @endif

        <span class="ml-2 text-xs text-subtle">
            @if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
            @else
                page {{ $paginator->currentPage() }}
            @endif
        </span>
    </nav>
@endif
