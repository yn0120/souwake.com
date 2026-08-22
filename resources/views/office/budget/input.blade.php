<x-office.layout title="家計簿">
    @php
        // URLが未設定の時だけ、保存先スプレッドシートの設定カードを出す
        $spreadsheetCardClass = $assign['spreadsheetUrl'] ? 'mb-4 hidden' : 'mb-4';
    @endphp

    <x-office.toast id="bdg-alert" />

    {{-- スプレッドシートURL設定。設定済みなら隠しておく。 --}}
    <x-office.card title="保存先スプレッドシート" :class="$spreadsheetCardClass">
        <form id="bdg-spreadsheet-form">
            <div class="grid gap-3 md:grid-cols-3">
                <div class="md:col-span-2">
                    <x-office.form.label for="bdg-spreadsheet-url">スプレッドシートURL</x-office.form.label>
                    <x-office.form.input type="url" id="bdg-spreadsheet-url"
                                         placeholder="https://docs.google.com/spreadsheets/d/..."
                                         :value="$assign['spreadsheetUrl']" />
                </div>
                <div class="flex items-end">
                    <x-office.button variant="outline-primary" type="submit">URLを保存</x-office.button>
                </div>
            </div>
            <p class="mt-2 text-xs text-body">
                ※ このスプレッドシートを、サービスアカウントのメールアドレスに編集者権限で共有しておく必要があります。
            </p>
        </form>
    </x-office.card>

    {{-- 入力フォーム --}}
    <x-office.card title="家計簿を入力">
        <form id="bdg-entry-form">
            <div class="grid gap-3 md:grid-cols-4">
                <div>
                    <x-office.form.label for="bdg-occurred-on">発生日</x-office.form.label>
                    <x-office.form.input type="tel" name="occurred_on" id="bdg-occurred-on"
                                         maxlength="8" inputmode="numeric" required />
                </div>
                <div>
                    <x-office.form.label for="bdg-amount">金額</x-office.form.label>
                    <x-office.form.input type="tel" name="amount" id="bdg-amount"
                                         inputmode="numeric" placeholder="9999" autocomplete="off" required />
                </div>
                <div>
                    <x-office.form.label for="bdg-account">口座</x-office.form.label>
                    <x-office.form.select name="account_id" id="bdg-account" required>
                        @foreach ($assign['accounts'] as $account)
                            <option value="{{ $account['id'] }}" @selected($account['id'] === $assign['defaultAccountId'])>{{ $account['name'] }}</option>
                        @endforeach
                        <option value="__add__">＋ 追加</option>
                    </x-office.form.select>
                </div>
                <div>
                    <x-office.form.label for="bdg-category">科目</x-office.form.label>
                    <x-office.form.select name="category_id" id="bdg-category" required>
                        @foreach ($assign['categories'] as $category)
                            <option value="{{ $category['id'] }}" @selected($category['id'] === $assign['defaultCategoryId'])>{{ $category['name'] }}</option>
                        @endforeach
                        <option value="__add__">＋ 追加</option>
                    </x-office.form.select>
                </div>
            </div>

            <div class="mt-3">
                <x-office.form.label for="bdg-memo">備考</x-office.form.label>
                <x-office.form.input name="memo" id="bdg-memo" />
            </div>

            <div class="mt-6 text-right">
                <x-office.button variant="success" type="submit">登録する</x-office.button>
            </div>
        </form>
    </x-office.card>

    <x-slot:scripts>
        <script>
            window.budgetConfig = {
                submitUrl: @json(route('officeBudgetCreateExecute', [], false)),
                accountCreateUrl: @json(route('officeBudgetAccountCreateExecute', [], false)),
                categoryCreateUrl: @json(route('officeBudgetCategoryCreateExecute', [], false)),
                spreadsheetUpdateUrl: @json(route('officeBudgetSpreadsheetEditExecute', [], false)),
                defaultAccountId: @json($assign['defaultAccountId']),
                defaultCategoryId: @json($assign['defaultCategoryId']),
                today: @json($assign['today']),
                csrfToken: @json(csrf_token()),
            };
        </script>
        @vite('resources/js/office/budget.js')
    </x-slot:scripts>
</x-office.layout>
