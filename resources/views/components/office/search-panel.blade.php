{{--
    一覧ページの検索条件パネル（開閉つき）。

    開閉はFlowbiteのcollapse。開いた状態で検索した時に、検索後も開いたまま返ってくるよう
    hidden の accordion パラメーターを resources/js/office.js が書き換えている（#headingSearch）。

      <x-office.search-panel :per-page="$assign['per_page']" :clear-href="route('officeAdminIndex')">
          <div class="col-span-6 md:col-span-3">...</div>
      </x-office.search-panel>
--}}
@props(['perPage' => null, 'clearHref' => null, 'action' => ''])

<div class="mb-4 rounded-xl bg-white p-4 shadow-sm">
    <h2>
        <button type="button"
                id="headingSearch"
                data-collapse-toggle="office-search"
                aria-expanded="{{ request()->accordion ? 'true' : 'false' }}"
                aria-controls="office-search"
                class="group flex w-full cursor-pointer items-center justify-between text-sm font-semibold text-warning-strong">
            検索条件
            <x-office.icon name="chevron-down"
                           class="size-4 transition-transform group-aria-expanded:rotate-180" />
        </button>
    </h2>

    <form method="GET" action="{{ $action }}">
        <input type="hidden" name="accordion" value="{{ request()->accordion }}">
        <input type="hidden" name="per_page" value="{{ $perPage }}">

        <div id="office-search" @class(['pt-4', 'hidden' => ! request()->accordion])>
            <div class="grid grid-cols-12 gap-3">
                {{ $slot }}
            </div>

            <div class="mt-4 space-y-2">
                <x-office.button variant="success" type="submit" class="w-full">検索する</x-office.button>
                @if ($clearHref)
                    <x-office.button variant="outline-dark" :href="$clearHref" class="w-full">検索条件をクリアする</x-office.button>
                @endif
            </div>
        </div>
    </form>
</div>
