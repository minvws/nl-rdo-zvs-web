@if ($paginator->hasPages())
    <nav
        class="pagination mt-3"
        aria-label="{{ __('general.page-navigation') }}">
        <ul class="pagination__list">
            {{-- Previous Page Link --}}

            @if ($paginator->onFirstPage())
                <li class="pagination__item disabled">
                    <span>{{ __('general.previous') }}</span>
                </li>
            @else
                <li class="pagination__item">
                    <a
                        class="pagination__link button"
                        href="{{ $paginator->previousPageUrl() }}"
                        rel="prev">
                        {{ __('general.previous') }}
                    </a>
                </li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="pagination__item">
                    <a
                        class="pagination__link button"
                        href="{{ $paginator->nextPageUrl() }}"
                        rel="next">
                        {{ __('general.next') }}
                    </a>
                </li>
            @else
                <li class="pagination__item disabled">
                    <span>{{ __('general.next') }}</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
