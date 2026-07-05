@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="pagination-link pagination-link--disabled">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pagination-link">Previous</a>
        @endif

        <span class="pagination-status">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pagination-link">Next</a>
        @else
            <span class="pagination-link pagination-link--disabled">Next</span>
        @endif
    </nav>
@endif
