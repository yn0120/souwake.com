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
@endphp
<div class="card p-3 mb-3" data-companion-row>
    <div class="row">
        <div class="col-6 pt-1">
            <span class="fw-bold"><span data-companion-number>{{ $number }}</span>人目の同伴者</span>
        </div>
        <div class="col-6 pt-1 text-end">
            <button type="button" data-companion-remove class="btn btn-sm btn-outline-danger">削除</button>
        </div>
    </div>

    <input type="hidden" name="companions[{{ $index }}][id]" value="{{ $companion['id'] ?? '' }}">

    <div class="row">
        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="{{ $rowId }}_name_sei" role="button">
            <span class="text-danger">※&nbsp;</span> お名前（姓・名）
        </label>
        <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6 gap-2">
            <input type="text" name="companions[{{ $index }}][name_sei]" value="{{ $companion['name_sei'] ?? '' }}" id="{{ $rowId }}_name_sei" class="form-control" placeholder="山田">
            <input type="text" name="companions[{{ $index }}][name_mei]" value="{{ $companion['name_mei'] ?? '' }}" id="{{ $rowId }}_name_mei" class="form-control" placeholder="花子">
        </div>
        @error("companions.{$index}.name_sei")
            <div class="col-md-3"></div>
            <div class="col-md-8">
                <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
            </div>
        @enderror
        @error("companions.{$index}.name_mei")
            <div class="col-md-3"></div>
            <div class="col-md-8">
                <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
            </div>
        @enderror
    </div>

    <div class="row">
        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="{{ $rowId }}_kana_sei" role="button">
            フリガナ（姓・名）
        </label>
        <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6 gap-2">
            <input type="text" name="companions[{{ $index }}][kana_sei]" value="{{ $companion['kana_sei'] ?? '' }}" id="{{ $rowId }}_kana_sei" class="form-control" placeholder="ヤマダ">
            <input type="text" name="companions[{{ $index }}][kana_mei]" value="{{ $companion['kana_mei'] ?? '' }}" id="{{ $rowId }}_kana_mei" class="form-control" placeholder="ハナコ">
        </div>
        @error("companions.{$index}.kana_sei")
            <div class="col-md-3"></div>
            <div class="col-md-8">
                <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
            </div>
        @enderror
        @error("companions.{$index}.kana_mei")
            <div class="col-md-3"></div>
            <div class="col-md-8">
                <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
            </div>
        @enderror
    </div>

    <div class="row">
        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="{{ $rowId }}_meal" role="button">
            お食事
        </label>
        <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
            <select name="companions[{{ $index }}][meal]" id="{{ $rowId }}_meal" class="form-control">
                <option value="">未選択</option>
                @foreach ($meals as $key => $label)
                    <option value="{{ $key }}" @selected(($companion['meal'] ?? null) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @error("companions.{$index}.meal")
            <div class="col-md-3"></div>
            <div class="col-md-8">
                <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
            </div>
        @enderror
    </div>

    <div class="row">
        <label class="col-md-3 col-form-label d-flex align-items-center pt-2 pb-0 py-md-2 fs-6 fw-bold" for="{{ $rowId }}_child_info" role="button">
            お子様連れの場合の追加情報
        </label>
        <div class="col-md-8 form-text d-flex align-items-center pt-0 pb-2 py-md-2 fs-6">
            <textarea name="companions[{{ $index }}][child_info]" id="{{ $rowId }}_child_info" rows="2" class="form-control" placeholder="例：2歳児1名、ベビーカー持参予定">{{ $companion['child_info'] ?? '' }}</textarea>
        </div>
        @error("companions.{$index}.child_info")
            <div class="col-md-3"></div>
            <div class="col-md-8">
                <div class="alert alert-danger mt-0 p-1 form-text" role="alert">{{ $message }}</div>
            </div>
        @enderror
    </div>
</div>
