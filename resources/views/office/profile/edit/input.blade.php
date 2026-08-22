<x-office.layout title="プロフィール編集">
    <x-office.toast id="prf-alert" />

    <x-office.card title="プロフィール編集">
        <form id="prf-form" enctype="multipart/form-data">
            <h2 class="mt-2 text-sm font-semibold text-heading">基本情報</h2>
            <div class="mt-2 grid gap-3 md:grid-cols-2">
                <div>
                    <x-office.form.label for="prf-name">氏名</x-office.form.label>
                    <x-office.form.input name="name" id="prf-name" :value="$assign['name']" required />
                </div>
                <div>
                    <x-office.form.label for="prf-email">メールアドレス</x-office.form.label>
                    <x-office.form.input type="email" name="email" id="prf-email" :value="$assign['email']" required />
                </div>
            </div>

            <h2 class="mt-6 text-sm font-semibold text-heading">パスワード変更（変更する場合のみ入力）</h2>
            <div class="mt-2 grid gap-3 md:grid-cols-2">
                <div>
                    <x-office.form.label for="prf-new-password">新しいパスワード</x-office.form.label>
                    <x-office.form.password name="new_password" id="prf-new-password" autocomplete="new-password" />
                </div>
            </div>

            <h2 class="mt-6 text-sm font-semibold text-heading">
                Googleサービスアカウント（家計簿のスプレッドシート書き込み用）
            </h2>
            <p class="mt-2 text-sm text-body">
                現在の設定：
                <span id="prf-service-account-status" class="font-semibold text-heading">
                    {{ $assign['serviceAccountEmail'] ?: '未設定' }}
                </span>
            </p>

            <details class="my-3">
                <summary class="cursor-pointer text-sm text-brand">JSON鍵ファイルの取得方法</summary>
                <ol class="mt-2 list-decimal space-y-1 pl-5 text-xs text-body">
                    <li><a href="https://console.cloud.google.com/" target="_blank" rel="noopener" class="text-brand hover:underline">Google Cloud Console</a>でプロジェクトを用意する</li>
                    <li>「APIとサービス」→「ライブラリ」から「Google Sheets API」を有効化する</li>
                    <li>「APIとサービス」→「認証情報」→「サービスアカウント」を作成する（ロール付与は不要）</li>
                    <li>作成したサービスアカウントの「キー」タブから、JSON形式で新しい鍵を作成・ダウンロードする</li>
                    <li>ダウンロードしたJSONファイルを下のフォームからアップロードする</li>
                    <li>家計簿で使うスプレッドシートを、JSON内の <code class="rounded bg-neutral-tertiary px-1">client_email</code>（アップロード後は上記に表示されます）に<strong>編集者権限</strong>で共有する</li>
                </ol>
            </details>

            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <x-office.form.label for="prf-service-account-json">JSON鍵ファイル</x-office.form.label>
                    <x-office.form.input type="file" name="service_account_json" id="prf-service-account-json"
                                         accept=".json,application/json" class="cursor-pointer" />
                </div>
            </div>

            <div class="mt-6 text-right">
                <x-office.button variant="success" type="submit">保存する</x-office.button>
            </div>
        </form>
    </x-office.card>

    <x-slot:scripts>
        <script>
            window.profileConfig = {
                updateUrl: @json(route('officeProfileEditExecute', [], false)),
                csrfToken: @json(csrf_token()),
            };
        </script>
        @vite('resources/js/office/profile.js')
    </x-slot:scripts>
</x-office.layout>
