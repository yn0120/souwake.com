@extends('office/parts/app')

@push('css')
    <link rel="stylesheet" href="/assets/vendor/css/pages/page-auth.css">
@endpush

@section('content')

    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                @include ('office/parts/item/alert')

                <div class="card px-sm-6 px-0">
                    <div class="card-body">
                        <form method="POST" action="{{ route('officeSecretsPasswordVerify', [], false) }}" class="mb-6" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-6 form-password-toggle">
                                <label class="form-label" for="password">パスワード</label>
                                <input type="password" id="password" class="form-control @error('password') is-invalid @enderror" name="password" autofocus>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-6">
                                <button type="submit" class="btn btn-primary d-grid w-100">確認</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
