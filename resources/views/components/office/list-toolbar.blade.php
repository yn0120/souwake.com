{{--
    一覧の上下に出す「該当件数」と「表示件数」。

      <x-office.list-toolbar :paginator="$assign['records']" :per-pages="$assign['perPages']" :per-page="$assign['per_page']" />
--}}
@props(['paginator', 'perPages' => null, 'perPage' => null])

<div class="flex flex-col gap-2 py-2 sm:flex-row sm:items-center sm:justify-between">
    <div class="text-sm text-body">該当件数 : {{ number_format($paginator->total()) }}件</div>

    @if ($perPages)
        <div class="flex items-center gap-2">
            <x-office.form.label for="perPage" inline>表示件数 :</x-office.form.label>
            <x-office.form.select name="per_page" id="perPage" :full-width="false">
                @foreach ($perPages as $key => $label)
                    <option value="{{ $key }}" @selected($perPage == $key)>{{ $label }}</option>
                @endforeach
            </x-office.form.select>
        </div>
    @endif
</div>

<div class="flex justify-end py-2">
    {{ $paginator->appends(request()->query())->links('office/parts/item/pagination') }}
</div>
