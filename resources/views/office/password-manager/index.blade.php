{{--
    パスワード管理。

    一覧・登録・編集はすべて非同期で、行のマークアップは
    resources/js/office/password-manager.js が生成する（クラス文字列は同 ui.js）。
--}}
<x-office.layout title="パスワード管理">
    <x-office.toast id="pwm-alert" />

    <x-office.card title="パスワード管理">
        <x-slot:actions>
            <x-office.button variant="primary" id="pwm-create-toggle">登録</x-office.button>
        </x-slot:actions>

        {{-- 新規登録パネル --}}
        <div id="pwm-create-panel" class="mb-4 rounded-lg border border-default p-4" style="display:none;">
            <h3 class="text-sm font-semibold text-heading">サイトを登録</h3>

            <form id="pwm-create-form" class="mt-3">
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <x-office.form.label for="pwm-create-name">サイト名</x-office.form.label>
                        <x-office.form.input name="name" id="pwm-create-name" required />
                    </div>
                </div>

                <div class="mt-3">
                    <x-office.form.label>項目</x-office.form.label>
                    <div id="pwm-create-items"></div>
                    <x-office.button variant="outline-secondary" size="sm" id="pwm-create-item-add" class="mt-2">
                        ＋ 項目を追加
                    </x-office.button>
                </div>

                <div class="mt-4 flex justify-end gap-2">
                    <x-office.button variant="outline-dark" id="pwm-create-cancel">キャンセル</x-office.button>
                    <x-office.button variant="success" type="submit">登録する</x-office.button>
                </div>
            </form>
        </div>

        {{-- 検索条件 --}}
        <form id="pwm-search-form" class="rounded-lg border border-default p-4">
            <div class="grid grid-cols-12 gap-3">
                <div class="col-span-6 md:col-span-3">
                    <x-office.form.label for="pwm-search-name">サイト名</x-office.form.label>
                    <x-office.form.input name="name" id="pwm-search-name" />
                </div>
                <div class="col-span-6 md:col-span-3">
                    <x-office.form.label for="pwm-search-keyword">キーワード（項目名・値）</x-office.form.label>
                    <x-office.form.input name="keyword" id="pwm-search-keyword" />
                </div>
                <div class="col-span-6 md:col-span-3">
                    <x-office.form.label for="pwm-search-sort">並び替え</x-office.form.label>
                    <x-office.form.select name="sort" id="pwm-search-sort">
                        <option value="display_order">表示順</option>
                        <option value="name">サイト名</option>
                        <option value="created_at">登録日時</option>
                    </x-office.form.select>
                </div>
                <div class="col-span-6 md:col-span-3">
                    <x-office.form.label for="pwm-search-direction">昇順・降順</x-office.form.label>
                    <x-office.form.select name="direction" id="pwm-search-direction">
                        <option value="asc">昇順</option>
                        <option value="desc">降順</option>
                    </x-office.form.select>
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-2">
                <x-office.button variant="outline-dark" id="pwm-search-clear">クリア</x-office.button>
                <x-office.button variant="success" type="submit">検索する</x-office.button>
            </div>
        </form>
        <p class="mt-2 text-xs text-body">
            ※ パスワード型の項目は暗号化して保存しているため、検索・並び替えの対象外です。
        </p>

        <div class="mt-4">
            <div id="pwm-list"></div>
            <div id="pwm-loading" class="py-4 text-center text-sm text-body">読み込み中...</div>
            <div id="pwm-empty" class="py-4 text-center text-sm text-body" style="display:none;">
                登録されているサイトがありません。
            </div>
        </div>
    </x-office.card>

    <x-slot:scripts>
        <script>
            @php
                $pwmListUrl = route('officePasswordManagerList', [], false);
                $pwmCreateUrl = route('officePasswordManagerCreateExecute', [], false);
                $pwmUpdateUrlBase = route('officePasswordManagerEditExecute', ['id' => '__ID__'], false);
                $pwmDeleteUrlBase = route('officePasswordManagerDeleteExecute', ['id' => '__ID__'], false);
                $pwmItemCreateUrlBase = route('officePasswordManagerItemCreateExecute', ['id' => '__ID__'], false);
                $pwmItemUpdateUrlBase = route('officePasswordManagerItemEditExecute', ['id' => '__ID__', 'itemId' => '__ITEM_ID__'], false);
                $pwmItemDeleteUrlBase = route('officePasswordManagerItemDeleteExecute', ['id' => '__ID__', 'itemId' => '__ITEM_ID__'], false);
            @endphp
            window.passwordManagerConfig = {
                listUrl: @json($pwmListUrl),
                createUrl: @json($pwmCreateUrl),
                updateUrlBase: @json($pwmUpdateUrlBase),
                deleteUrlBase: @json($pwmDeleteUrlBase),
                itemCreateUrlBase: @json($pwmItemCreateUrlBase),
                itemUpdateUrlBase: @json($pwmItemUpdateUrlBase),
                itemDeleteUrlBase: @json($pwmItemDeleteUrlBase),
                itemTypes: @json($assign['itemTypes']),
                csrfToken: @json(csrf_token()),
            };
        </script>
        @vite('resources/js/office/password-manager.js')
    </x-slot:scripts>
</x-office.layout>
