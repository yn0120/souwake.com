{{--
    同伴者（連名）1名分の入力行。

    ページ表示時の行（old()の復元を含む）と、JSが「＋同伴者を追加」で複製するテンプレートの
    両方から読み込む。テンプレートとして読み込む場合は $index に '__INDEX__'、$number に
    '__NUMBER__' を渡し、JSが実際の番号へ置換する（resources/js/wedding.js）。

    @param int|string $index    name属性に使う添字
    @param int|string $number   画面に表示する「○人目」の番号
    @param array      $companion 初期値（old()の値）
--}}
@php
    $companion = $companion ?? [];
    $rowId = "companions_{$index}";
@endphp
<div class="space-y-4 rounded-2xl border border-sand-200 bg-white/50 p-5" data-companion-row>
    <div class="flex items-center justify-between gap-3">
        <p class="text-sm font-medium text-ink-800">
            <span data-companion-number>{{ $number }}</span>人目の同伴者
        </p>
        <button type="button" data-companion-remove class="shrink-0 rounded-full border border-clay-500/60 px-3.5 py-1 text-xs text-clay-700 transition hover:bg-clay-600 hover:text-sand-50">
            削除
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="{{ $labelClass }}" for="{{ $rowId }}_name_sei">同伴者お名前（姓）</label>
            <input class="{{ $inputClass }}" type="text" id="{{ $rowId }}_name_sei" name="companions[{{ $index }}][name_sei]" placeholder="山田" value="{{ $companion['name_sei'] ?? '' }}">
            @error("companions.{$index}.name_sei") <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}" for="{{ $rowId }}_name_mei">同伴者お名前（名）</label>
            <input class="{{ $inputClass }}" type="text" id="{{ $rowId }}_name_mei" name="companions[{{ $index }}][name_mei]" placeholder="花子" value="{{ $companion['name_mei'] ?? '' }}">
            @error("companions.{$index}.name_mei") <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="{{ $labelClass }}" for="{{ $rowId }}_kana_sei">同伴者フリガナ（姓）</label>
            <input class="{{ $inputClass }}" type="text" id="{{ $rowId }}_kana_sei" name="companions[{{ $index }}][kana_sei]" placeholder="ヤマダ" value="{{ $companion['kana_sei'] ?? '' }}">
            @error("companions.{$index}.kana_sei") <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="{{ $labelClass }}" for="{{ $rowId }}_kana_mei">同伴者フリガナ（名）</label>
            <input class="{{ $inputClass }}" type="text" id="{{ $rowId }}_kana_mei" name="companions[{{ $index }}][kana_mei]" placeholder="ハナコ" value="{{ $companion['kana_mei'] ?? '' }}">
            @error("companions.{$index}.kana_mei") <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $rowId }}_meal">同伴者のお食事</label>
        <select class="{{ $inputClass }}" id="{{ $rowId }}_meal" name="companions[{{ $index }}][meal]">
            <option value="">選択してください</option>
            @foreach (App\Models\WeddingRsvpCompanionModel::mealOptions() as $value => $label)
                <option value="{{ $value }}" @selected(($companion['meal'] ?? null) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error("companions.{$index}.meal") <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="{{ $labelClass }}" for="{{ $rowId }}_child_info">お子様連れの場合の追加情報</label>
        <p class="{{ $noteClass }} mb-2">年齢、ベビーカーの持ち込みの有無などをご記入ください。</p>
        <textarea class="{{ $inputClass }}" id="{{ $rowId }}_child_info" name="companions[{{ $index }}][child_info]" rows="3" placeholder="例：2歳児1名、ベビーカー持参予定">{{ $companion['child_info'] ?? '' }}</textarea>
        @error("companions.{$index}.child_info") <p class="{{ $errorClass }}">{{ $message }}</p> @enderror
    </div>
</div>
