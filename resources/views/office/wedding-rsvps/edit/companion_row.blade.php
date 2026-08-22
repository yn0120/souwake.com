{{--
    同伴者（連名）1名分の入力行。

    ページ表示時の行（old()の復元を含む）と、JSが「＋同伴者を追加」で複製するテンプレートの
    両方から読み込む。テンプレートとして読み込む場合は $index に '__INDEX__'、$number に
    '__NUMBER__' を渡し、JSが実際の番号へ置換する。

    @param int|string $index     name属性に使う添字
    @param int|string $number    画面に表示する「○人目」の番号
    @param array      $companion 初期値
    @param array      $meals     お食事の選択肢
--}}
@php
    $companion = $companion ?? [];
    $rowId = "companions_{$index}";

    // 入力欄のid（ラベルのforに使う）
    $ids = [
        'nameSei' => "{$rowId}_name_sei",
        'kanaSei' => "{$rowId}_kana_sei",
        'meal' => "{$rowId}_meal",
        'childInfo' => "{$rowId}_child_info",
    ];

    // エラー表示に使うキー。姓と名は1行にまとめているため配列で渡す。
    $errorKeys = [
        'name' => ["companions.{$index}.name_sei", "companions.{$index}.name_mei"],
        'kana' => ["companions.{$index}.kana_sei", "companions.{$index}.kana_mei"],
        'meal' => "companions.{$index}.meal",
        'childInfo' => "companions.{$index}.child_info",
    ];
@endphp

<div class="mb-3 rounded-lg border border-default p-3" data-companion-row>
    <div class="flex items-center justify-between">
        <span class="text-sm font-bold text-heading">
            <span data-companion-number>{{ $number }}</span>人目の同伴者
        </span>
        <x-office.button variant="outline-danger" size="sm" data-companion-remove>削除</x-office.button>
    </div>

    <input type="hidden" name="companions[{{ $index }}][id]" value="{{ $companion['id'] ?? '' }}">

    <x-office.form.row label="お名前（姓・名）" :for="$ids['nameSei']" required :name="$errorKeys['name']">
        <div class="flex gap-2">
            <x-office.form.input name="companions[{{ $index }}][name_sei]" :id="$ids['nameSei']"
                                 :value="$companion['name_sei'] ?? ''" placeholder="山田" />
            <x-office.form.input name="companions[{{ $index }}][name_mei]" id="{{ $rowId }}_name_mei"
                                 :value="$companion['name_mei'] ?? ''" placeholder="花子" />
        </div>
    </x-office.form.row>

    <x-office.form.row label="フリガナ（姓・名）" :for="$ids['kanaSei']" :name="$errorKeys['kana']">
        <div class="flex gap-2">
            <x-office.form.input name="companions[{{ $index }}][kana_sei]" :id="$ids['kanaSei']"
                                 :value="$companion['kana_sei'] ?? ''" placeholder="ヤマダ" />
            <x-office.form.input name="companions[{{ $index }}][kana_mei]" id="{{ $rowId }}_kana_mei"
                                 :value="$companion['kana_mei'] ?? ''" placeholder="ハナコ" />
        </div>
    </x-office.form.row>

    <x-office.form.row label="お食事" :for="$ids['meal']" :name="$errorKeys['meal']">
        <x-office.form.select name="companions[{{ $index }}][meal]" :id="$ids['meal']">
            <option value="">未選択</option>
            @foreach ($meals as $key => $label)
                <option value="{{ $key }}" @selected(($companion['meal'] ?? null) === $key)>{{ $label }}</option>
            @endforeach
        </x-office.form.select>
    </x-office.form.row>

    <x-office.form.row label="お子様連れの場合の追加情報" :for="$ids['childInfo']" :name="$errorKeys['childInfo']">
        <x-office.form.textarea name="companions[{{ $index }}][child_info]" :id="$ids['childInfo']" rows="2"
                                placeholder="例：2歳児1名、ベビーカー持参予定">{{ $companion['child_info'] ?? '' }}</x-office.form.textarea>
    </x-office.form.row>
</div>
