@if (session('success') || session('error'))
    <div class="row pb-3">
        <div class="col-12">
            @if (session('success'))
                <p class="alert alert-success p-1 text-break" role="alert">{!! nl2br(e(session('success'))) !!}</p>
            @endif
            @if (session('error'))
                <p class="alert alert-danger p-1 text-break" role="alert">{!! nl2br(e(session('error'))) !!}</p>
            @endif
        </div>
    </div>
@endif

{{--
    <div class="row pb-3">
        <div class="col-12">
            <p class="alert alert-success p-1 text-break" role="alert">サクセスメッセージです。</p>
            <p class="alert alert-danger p-1 text-break" role="alert">エラーメッセージです。</p>
        </div>
    </div>
--}}
