@extends('office/parts/app')

@section('meta')
    <title>エラー | {{ config('app.name') }}</title>
@endsection

@push('css')
    <link rel="stylesheet" href="/backend/vendor/css/pages/page-misc.css">
@endpush

@section('content')

    <div class="container-fluid flex-grow-1 container-p-y">
        <div class="misc-wrapper">
            <h1 class="mb-2 mx-2">{{ $assign['code'] }}</h1>
            <h4 class="mb-2 mx-2">{{ $assign['msg'] }}</h4>
            <a href="javascript:history.back();" class="btn btn-outline-dark">戻る</a>
        </div>
    </div>

@endsection

@push ('js')

@endpush
