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
            @if ($assign['layout'] === 'transfer')
                <div class="grid gap-3 md:grid-cols-4">
                    <div>
                        <x-office.form.label for="bdg-occurred-on">日付</x-office.form.label>
                        <x-office.form.input type="tel" name="occurred_on" id="bdg-occurred-on"
                                             maxlength="8" inputmode="numeric" required />
                    </div>
                    <div>
                        <x-office.form.label for="bdg-type">区分</x-office.form.label>
                        <x-office.form.select name="type" id="bdg-type" required>
                            @foreach ($assign['types'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-office.form.select>
                    </div>
                    <div class="md:col-span-2">
                        <x-office.form.label for="bdg-content">内容</x-office.form.label>
                        <x-office.form.input type="text" name="content" id="bdg-content" required />
                    </div>
                    <div>
                        <x-office.form.label for="bdg-deposit">入金額</x-office.form.label>
                        <x-office.form.input type="tel" name="deposit_amount" id="bdg-deposit"
                                             inputmode="numeric" placeholder="9999" autocomplete="off" />
                    </div>
                    <div>
                        <x-office.form.label for="bdg-withdrawal">出金額</x-office.form.label>
                        <x-office.form.input type="tel" name="withdrawal_amount" id="bdg-withdrawal"
                                             inputmode="numeric" placeholder="9999" autocomplete="off" />
                    </div>
                    <div>
                        <x-office.form.label for="bdg-balance">残高</x-office.form.label>
                        <x-office.form.input type="tel" name="balance" id="bdg-balance"
                                             inputmode="numeric" placeholder="9999" autocomplete="off" />
                    </div>
                    <div>
                        <x-office.form.label for="bdg-member">利用者</x-office.form.label>
                        <x-office.form.select name="member" id="bdg-member" required>
                            @foreach ($assign['members'] as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-office.form.select>
                    </div>
                </div>

                <div class="mt-3">
                    <x-office.form.label for="bdg-memo">備考</x-office.form.label>
                    <x-office.form.textarea name="memo" id="bdg-memo" />
                </div>
            @else
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
            @endif

            <div class="mt-6 text-right">
                <x-office.button variant="success" type="submit">登録する</x-office.button>
            </div>
        </form>
    </x-office.card>

    <x-slot:scripts>
        <script>
            window.budgetConfig = {
                layout: @json($assign['layout']),
                submitUrl: @json(route('officeBudgetCreateExecute', [], false)),
                spreadsheetUpdateUrl: @json(route('officeBudgetSpreadsheetEditExecute', [], false)),
                today: @json($assign['today']),
                csrfToken: @json(csrf_token()),
                @if ($assign['layout'] === 'default')
                accountCreateUrl: @json(route('officeBudgetAccountCreateExecute', [], false)),
                categoryCreateUrl: @json(route('officeBudgetCategoryCreateExecute', [], false)),
                defaultAccountId: @json($assign['defaultAccountId']),
                defaultCategoryId: @json($assign['defaultCategoryId']),
                @endif
            };
        </script>
        @vite('resources/js/office/budget.js')
    </x-slot:scripts>
</x-office.layout>
