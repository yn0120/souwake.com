@if ($paginator->hasPages())
    <nav class="d-flex justify-content-end">
        <div class="d-md-none d-flex justify-content-end flex-fill">
            <div>
                <ul class="pagination">
                    {{-- First Page Link --}}
                    @if ($paginator->currentPage() == 1)
                        <li class="page-item disabled" aria-disabled="true" aria-label="<<">
                            <span class="page-link" aria-hidden="true"><<</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url(1) }}" rel="first" aria-label="<<"><<</a>
                        </li>
                    @endif
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="<">
                            <span class="page-link" aria-hidden="true"><</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="<"><</a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @php
                        $linkNum = 3;
                        $lastPage = $paginator->lastPage();
                        $currentPage = $paginator->currentPage();

                        if ($lastPage > $linkNum) {
                            $halfLinks = floor($linkNum / 2);

                            if ($currentPage <= $halfLinks) {
                                $startPage = 1;
                                $endPage = $linkNum;
                            } elseif ($currentPage > $lastPage - $halfLinks) {
                                $startPage = $lastPage - ($linkNum - 1);
                                $endPage = $lastPage;
                            } else {
                                $startPage = $currentPage - $halfLinks;
                                $endPage = $currentPage + $halfLinks;
                            }
                        } else {
                            $startPage = 1;
                            $endPage = $lastPage;
                        }
                    @endphp

                    @for ($i = $startPage; $i <= $endPage; $i++)
                        <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                            @if ($i == $currentPage)
                                <span class="page-link">{{ $i }}</span>
                            @else
                                <a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a>
                            @endif
                        </li>
                    @endfor

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label=">">></a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label=">">
                            <span class="page-link" aria-hidden="true">></span>
                        </li>
                    @endif
                    @if ($paginator->currentPage() === $paginator->lastPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label=">>">
                            <span class="page-link" aria-hidden="true">>></span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}" rel="last" aria-label=">>">>></a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="d-none d-md-block d-lg-none flex-md-fill d-md-flex align-items-md-center justify-content-end">
            <div>
                <ul class="pagination">
                    {{-- First Page Link --}}
                    @if ($paginator->currentPage() == 1)
                        <li class="page-item disabled" aria-disabled="true" aria-label="<<">
                            <span class="page-link" aria-hidden="true"><<</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url(1) }}" rel="first" aria-label="<<"><<</a>
                        </li>
                    @endif
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="<">
                            <span class="page-link" aria-hidden="true"><</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="<"><</a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    {{-- Pagination Elements --}}
                    @php
                        $linkNum = 10;
                        $lastPage = $paginator->lastPage();
                        $currentPage = $paginator->currentPage();

                        if ($lastPage > $linkNum) {
                            $halfLinks = floor($linkNum / 2);

                            if ($currentPage <= $halfLinks) {
                                $startPage = 1;
                                $endPage = $linkNum;
                            } elseif ($currentPage > $lastPage - $halfLinks) {
                                $startPage = $lastPage - ($linkNum - 1);
                                $endPage = $lastPage;
                            } else {
                                $startPage = $currentPage - $halfLinks;
                                $endPage = $currentPage + $halfLinks;
                            }
                        } else {
                            $startPage = 1;
                            $endPage = $lastPage;
                        }
                    @endphp
                    @for ($i = $startPage; $i <= $endPage; $i++)
                        <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                            @if ($i == $currentPage)
                                <span class="page-link">{{ $i }}</span>
                            @else
                                <a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a>
                            @endif
                        </li>
                    @endfor

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label=">">></a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label=">">
                            <span class="page-link" aria-hidden="true">></span>
                        </li>
                    @endif
                    @if ($paginator->currentPage() === $paginator->lastPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label=">>">
                            <span class="page-link" aria-hidden="true">>></span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}" rel="last" aria-label=">>">>></a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="d-none d-lg-block flex-lg-fill d-lg-flex align-items-lg-center justify-content-end">
            <div>
                <ul class="pagination">
                    {{-- First Page Link --}}
                    @if ($paginator->currentPage() == 1)
                        <li class="page-item disabled" aria-disabled="true" aria-label="<<">
                            <span class="page-link" aria-hidden="true"><<</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url(1) }}" rel="first" aria-label="<<"><<</a>
                        </li>
                    @endif
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label="<">
                            <span class="page-link" aria-hidden="true"><</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="<"><</a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label=">">></a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true" aria-label=">">
                            <span class="page-link" aria-hidden="true">></span>
                        </li>
                    @endif
                    @if ($paginator->currentPage() === $paginator->lastPage())
                        <li class="page-item disabled" aria-disabled="true" aria-label=">>">
                            <span class="page-link" aria-hidden="true">>></span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->url($paginator->lastPage()) }}" rel="last" aria-label=">>">>></a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
@endif
