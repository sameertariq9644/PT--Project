@if ($paginator->hasPages())
    <nav class="pager" aria-label="Pagination">
        <span class="pager__info">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</span>
        <div class="pager__links">
            @if ($paginator->onFirstPage())
                <span class="tab is-disabled">Previous</span>
            @else
                <a class="tab" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif

            <span class="pager__page">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

            @if ($paginator->hasMorePages())
                <a class="tab" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="tab is-disabled">Next</span>
            @endif
        </div>
    </nav>
@endif
