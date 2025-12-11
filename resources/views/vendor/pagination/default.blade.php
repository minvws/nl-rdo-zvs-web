@if ($paginator->hasPages())
    <nav class="pagination pagination--numbered mt-5">
        <ul class="pagination__list">
            {{-- First Page Link --}}

            {{-- Previous Page Link --}}

            @if ($paginator->onFirstPage())
                <li
                    class="disabled pagination__item pagination__item--extended"
                    aria-disabled="true">
                    <span>
                        <x-tabler-chevrons-left
                            aria-hidden="true"
                            focusable="false" />
                        {{ __('general.first') }}
                    </span>
                </li>
                <li
                    class="disabled pagination__item pagination__item--extended"
                    aria-disabled="true">
                    <span>
                        <x-tabler-chevron-left
                            aria-hidden="true"
                            focusable="false" />
                        {{ __('general.previous') }}
                    </span>
                </li>
            @else
                <li class="pagination__item pagination__item--extended">
                    <a
                        class="pagination__link"
                        href="{{ $paginator->url(1) }}"
                        rel="prev">
                        <span>
                            <x-tabler-chevrons-left
                                aria-hidden="true"
                                focusable="false" />
                            {{ __('general.first') }}
                        </span>
                    </a>
                </li>
                <li class="pagination__item pagination__item--extended">
                    <a
                        href="{{ $paginator->previousPageUrl() }}"
                        class="pagination__link"
                        rel="prev">
                        <span>
                            <x-tabler-chevron-left
                                aria-hidden="true"
                                focusable="false" />
                            {{ __('general.previous') }}
                        </span>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li
                        class="disabled pagination__item"
                        aria-disabled="true">
                        <span>{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li
                                class="active pagination__item"
                                aria-current="page">
                                <span>{{ $page }}</span>
                            </li>
                        @else
                            <li class="pagination__item">
                                <a
                                    href="{{ $url }}"
                                    class="pagination__link">
                                    {{ $page }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}

            @if ($paginator->hasMorePages())
                <li class="pagination__item pagination__item--extended">
                    <a
                        href="{{ $paginator->nextPageUrl() }}"
                        class="pagination__link"
                        rel="next">
                        <span>
                            {{ __('general.next') }}
                            <x-tabler-chevron-right
                                aria-hidden="true"
                                focusable="false" />
                        </span>
                    </a>
                </li>
            @else
                <li
                    class="disabled pagination__item pagination__item--extended"
                    aria-disabled="true">
                    <span>
                        {{ __('general.next') }}
                        <x-tabler-chevron-right
                            aria-hidden="true"
                            focusable="false" />
                    </span>
                </li>
            @endif

            {{-- Last Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="pagination__item pagination__item--extended">
                    <a
                        class="pagination__link"
                        href="{{ $paginator->url($paginator->lastPage()) }}"
                        rel="last">
                        {{ __('general.last') }}
                        <x-tabler-chevrons-right
                            aria-hidden="true"
                            focusable="false" />
                    </a>
                </li>
            @else
                <li
                    class="disabled pagination__item pagination__item--extended"
                    aria-disabled="true">
                    <span>
                        {{ __('general.last') }}
                        <x-tabler-chevrons-right
                            aria-hidden="true"
                            focusable="false" />
                        &raquo;
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
