{{--
    ページ送り（Laravelのpaginator用ビュー）。

    画面幅ごとに出すページ番号の数を変える。Sneat時代と同じ振り分け:
      スマホ … 3件 / タブレット … 10件 / PC … Laravel既定の窓（$elements）
--}}
@if ($paginator->hasPages())
    @php
        $variants = [
            ['links' => 3, 'visibility' => 'md:hidden'],
            ['links' => 10, 'visibility' => 'hidden md:flex lg:hidden'],
            // null = $elements（Laravelが用意する「…」入りの窓）をそのまま使う
            ['links' => null, 'visibility' => 'hidden lg:flex'],
        ];

        $itemBase = 'inline-flex min-w-9 items-center justify-center rounded-lg border px-2 py-1.5 text-sm';
        $itemLink = "{$itemBase} border-default bg-white text-body transition-colors hover:bg-brand-softer hover:text-brand";
        $itemActive = "{$itemBase} border-brand bg-brand font-semibold text-white";
        $itemDisabled = "{$itemBase} border-default bg-neutral-tertiary text-fg-disabled";

        $lastPage = $paginator->lastPage();
        $currentPage = $paginator->currentPage();
    @endphp

    <nav aria-label="ページ送り" class="flex justify-end">
        @foreach ($variants as $variant)
            @php
                if ($variant['links']) {
                    $linkNum = $variant['links'];
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
                }
            @endphp

            <ul class="{{ $variant['visibility'] }} flex flex-wrap items-center gap-1">
                {{-- 先頭へ --}}
                <li>
                    @if ($currentPage == 1)
                        <span aria-disabled="true" aria-label="先頭のページ" class="{{ $itemDisabled }}">
                            <x-office.icon name="chevron-double-left" class="size-4" />
                        </span>
                    @else
                        <a href="{{ $paginator->url(1) }}" rel="first" aria-label="先頭のページ" class="{{ $itemLink }}">
                            <x-office.icon name="chevron-double-left" class="size-4" />
                        </a>
                    @endif
                </li>

                {{-- 前へ --}}
                <li>
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="前のページ" class="{{ $itemDisabled }}">
                            <x-office.icon name="chevron-left" class="size-4" />
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="前のページ" class="{{ $itemLink }}">
                            <x-office.icon name="chevron-left" class="size-4" />
                        </a>
                    @endif
                </li>

                {{-- ページ番号 --}}
                @if ($variant['links'])
                    @for ($i = $startPage; $i <= $endPage; $i++)
                        <li>
                            @if ($i == $currentPage)
                                <span aria-current="page" class="{{ $itemActive }}">{{ $i }}</span>
                            @else
                                <a href="{{ $paginator->url($i) }}" class="{{ $itemLink }}">{{ $i }}</a>
                            @endif
                        </li>
                    @endfor
                @else
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <li><span aria-disabled="true" class="{{ $itemDisabled }}">{{ $element }}</span></li>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                <li>
                                    @if ($page == $currentPage)
                                        <span aria-current="page" class="{{ $itemActive }}">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" class="{{ $itemLink }}">{{ $page }}</a>
                                    @endif
                                </li>
                            @endforeach
                        @endif
                    @endforeach
                @endif

                {{-- 次へ --}}
                <li>
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="次のページ" class="{{ $itemLink }}">
                            <x-office.icon name="chevron-right" class="size-4" />
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="次のページ" class="{{ $itemDisabled }}">
                            <x-office.icon name="chevron-right" class="size-4" />
                        </span>
                    @endif
                </li>

                {{-- 末尾へ --}}
                <li>
                    @if ($currentPage === $lastPage)
                        <span aria-disabled="true" aria-label="最後のページ" class="{{ $itemDisabled }}">
                            <x-office.icon name="chevron-double-right" class="size-4" />
                        </span>
                    @else
                        <a href="{{ $paginator->url($lastPage) }}" rel="last" aria-label="最後のページ" class="{{ $itemLink }}">
                            <x-office.icon name="chevron-double-right" class="size-4" />
                        </a>
                    @endif
                </li>
            </ul>
        @endforeach
    </nav>
@endif
