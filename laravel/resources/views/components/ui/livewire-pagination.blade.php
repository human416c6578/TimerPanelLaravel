{{-- Steam-style pager for the Livewire lists: buttons, not links, so nothing reloads. --}}
@if ($paginator->hasPages())
    <nav class="pagination flex flex-wrap items-center gap-1" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="is-disabled" aria-disabled="true">&lsaquo;</span>
        @else
            <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" aria-label="Previous page">&lsaquo;</button>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="is-disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')">{{ $page }}</button>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" aria-label="Next page">&rsaquo;</button>
        @else
            <span class="is-disabled" aria-disabled="true">&rsaquo;</span>
        @endif

        @if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <span class="ms-2 !border-0 !bg-transparent text-[11px] text-subtle">
                {{ number_format($paginator->firstItem() ?? 0) }}–{{ number_format($paginator->lastItem() ?? 0) }} of {{ number_format($paginator->total()) }}
            </span>
        @endif
    </nav>
@endif
