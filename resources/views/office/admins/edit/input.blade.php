<x-office.layout title="管理者編集">
    <x-office.card title="管理者編集">
        <form method="POST" action="{{ route('officeAdminEditConfirm', ['id' => $assign['record']->id], false) }}" enctype="multipart/form-data">
            @csrf

            <x-office.form.row label="氏名" for="name" required name="name">
                <x-office.form.input name="name" id="name" :value="old('name', $assign['record']->name)" />
            </x-office.form.row>

            <x-office.form.row label="権限" for="role_id" required name="role_id">
                <x-office.form.select name="role_id" id="role_id">
                    <option value="">未選択</option>
                    @foreach ($assign['roles'] as $key => $role)
                        <option value="{{ $key }}" @selected($key == old('role_id', $assign['record']->role_id))>{{ $role }}</option>
                    @endforeach
                </x-office.form.select>
            </x-office.form.row>

            <x-office.form.row label="メールアドレス" for="email" required name="email">
                <x-office.form.input name="email" id="email" :value="old('email', $assign['record']->email)" />
            </x-office.form.row>

            <x-office.form.row label="パスワード" for="password" name="password" help="未入力の場合は更新しません。">
                <x-office.form.password name="password" id="password" value=""
                                        autocapitalize="off" autocomplete="new-password" />
            </x-office.form.row>

            @if ($assign['record']->login_locked_at)
                @php
                    $lockedAt = e($assign['record']->login_locked_at_formatted);
                    $loginLockHelp = "ログインロックを解除する場合はチェックしてください。<br>{$lockedAt} 後に自動的に解除されます。";
                @endphp
                <x-office.form.row label="ログインロック" name="login_locked_at" :help="$loginLockHelp">
                    <input type="hidden" name="login_locked_at" value="0">
                    <x-office.form.check name="login_locked_at" id="login_locked_at" value="1">解除する</x-office.form.check>
                </x-office.form.row>
            @else
                <input type="hidden" name="login_locked_at" value="0">
            @endif

            <x-office.form.row label="退職日" for="terminated_at" name="terminated_at"
                               help="管理者が退職されたり、一時的に有効化したアカウントを無効化する場合にご入力ください。">
                <x-office.form.datepicker name="terminated_at" id="terminated_at"
                                          :value="old('terminated_at', $assign['record']->terminated_at ? date('Y/m/d', strtotime($assign['record']->terminated_at)) : null)" />
            </x-office.form.row>

            <div class="mt-6 space-y-2">
                <x-office.button variant="success" type="submit" id="submit" class="w-full">確認する</x-office.button>
                @if (in_array('officeAdminIndex*', Auth::user()->routes()))
                    <x-office.button variant="outline-dark" class="w-full"
                                     :href="route('officeAdminIndex', session('officeAdminIndexSearchParams'))">前のページに戻る</x-office.button>
                @endif
            </div>
        </form>
    </x-office.card>
</x-office.layout>
