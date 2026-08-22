{{-- 直前の処理結果（session）を出す。レイアウトが全ページで呼ぶため、各ページで書く必要は無い。 --}}
@if (session('success') || session('error'))
    <div class="mb-4 space-y-2">
        @if (session('success'))
            <p role="alert"
               class="rounded-lg border border-success-subtle bg-success-soft p-3 text-sm break-words text-success-strong">
                {!! nl2br(e(session('success'))) !!}
            </p>
        @endif
        @if (session('error'))
            <p role="alert"
               class="rounded-lg border border-danger-subtle bg-danger-soft p-3 text-sm break-words text-danger-strong">
                {!! nl2br(e(session('error'))) !!}
            </p>
        @endif
    </div>
@endif
