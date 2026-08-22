{{--
    一覧表。

    横に長い表はこのコンポーネントの中だけが横スクロールする（ページ全体は横に揺れない）。
    セルをクリックさせたい時は <x-office.table.td clickable> を使う。

      <x-office.table>
          <x-slot:head>
              <x-office.table.th>権限名</x-office.table.th>
          </x-slot:head>
          <tr>...</tr>
      </x-office.table>
--}}
<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="w-full border-collapse text-sm">
        @isset ($head)
            <thead>
                <tr class="bg-dark">{{ $head }}</tr>
            </thead>
        @endisset
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
