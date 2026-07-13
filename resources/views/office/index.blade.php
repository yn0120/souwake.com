@extends('office/parts/app')

@section('meta')
    <title>{{ config('app.name') }}</title>
@endsection

@push('css')

@endpush

@section('content')

    <div class="container-fluid flex-grow-1 container-p-y">
        <!-- Layout wrapper -->
        <div class="layout-wrapper layout-content-navbar">
            <div class="layout-container">
                <!-- Menu -->
                @include ('office/parts/side')
                <!-- / Menu -->
                <!-- Layout container -->
                <div class="layout-page">
                    <!-- Content wrapper -->
                    <div class="content-wrapper">
                        <!-- Content -->
                        <div class="container-fluid flex-grow-1 container-p-y">
                            {{-- エラー/サクセス メッセージ --}}
                            @include ('office/parts/item/alert')
                            <div class="row">
                                <!-- Transactions -->
                                <div class="col-md-6 col-lg-4 order-2 mb-6">
                                    <div class="card h-100">
                                        <div class="card-header d-flex align-items-center justify-content-between">
                                            <h5 class="card-title m-0 me-2">
                                                Transactions
                                            </h5>
                                            <div class="dropdown">
                                                <button class="btn text-body-secondary p-0" type="button" id="transactionID" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" >
                                                    <i class="icon-base bx bx-dots-vertical-rounded icon-lg">
                                                    </i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="transactionID" >
                                                    <a class="dropdown-item" href="javascript:void(0);">
                                                        Last 28 Days
                                                    </a>
                                                    <a class="dropdown-item" href="javascript:void(0);">
                                                        Last Month
                                                    </a>
                                                    <a class="dropdown-item" href="javascript:void(0);">
                                                        Last Year
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body pt-4">
                                            <ul class="p-0 m-0">
                                                <li class="d-flex align-items-center mb-6">
                                                    <div class="avatar flex-shrink-0 me-3">
                                                        <img src="/assets/img/icons/unicons/paypal.png" alt="User" class="rounded" />
                                                    </div>
                                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2" >
                                                        <div class="me-2">
                                                            <small class="d-block">
                                                                Paypal
                                                            </small>
                                                            <h6 class="fw-normal mb-0">
                                                                Send money
                                                            </h6>
                                                        </div>
                                                        <div class="user-progress d-flex align-items-center gap-2">
                                                            <h6 class="fw-normal mb-0">
                                                                +82.6
                                                            </h6>
                                                            <span class="text-body-secondary">
                                                                USD
                                                            </span>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li class="d-flex align-items-center mb-6">
                                                    <div class="avatar flex-shrink-0 me-3">
                                                        <img src="/assets/img/icons/unicons/wallet.png" alt="User" class="rounded" />
                                                    </div>
                                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2" >
                                                        <div class="me-2">
                                                            <small class="d-block">
                                                                Wallet
                                                            </small>
                                                            <h6 class="fw-normal mb-0">
                                                                Mac'D
                                                            </h6>
                                                        </div>
                                                        <div class="user-progress d-flex align-items-center gap-2">
                                                            <h6 class="fw-normal mb-0">
                                                                +270.69
                                                            </h6>
                                                            <span class="text-body-secondary">
                                                                USD
                                                            </span>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li class="d-flex align-items-center mb-6">
                                                    <div class="avatar flex-shrink-0 me-3">
                                                        <img src="/assets/img/icons/unicons/chart.png" alt="User" class="rounded" />
                                                    </div>
                                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2" >
                                                        <div class="me-2">
                                                            <small class="d-block">
                                                                Transfer
                                                            </small>
                                                            <h6 class="fw-normal mb-0">
                                                                Refund
                                                            </h6>
                                                        </div>
                                                        <div class="user-progress d-flex align-items-center gap-2">
                                                            <h6 class="fw-normal mb-0">
                                                                +637.91
                                                            </h6>
                                                            <span class="text-body-secondary">
                                                                USD
                                                            </span>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li class="d-flex align-items-center mb-6">
                                                    <div class="avatar flex-shrink-0 me-3">
                                                        <img src="/assets/img/icons/unicons/cc-primary.png" alt="User" class="rounded" />
                                                    </div>
                                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2" >
                                                        <div class="me-2">
                                                            <small class="d-block">
                                                                Credit Card
                                                            </small>
                                                            <h6 class="fw-normal mb-0">
                                                                Ordered Food
                                                            </h6>
                                                        </div>
                                                        <div class="user-progress d-flex align-items-center gap-2">
                                                            <h6 class="fw-normal mb-0">
                                                                -838.71
                                                            </h6>
                                                            <span class="text-body-secondary">
                                                                USD
                                                            </span>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li class="d-flex align-items-center mb-6">
                                                    <div class="avatar flex-shrink-0 me-3">
                                                        <img src="/assets/img/icons/unicons/wallet.png" alt="User" class="rounded" />
                                                    </div>
                                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2" >
                                                        <div class="me-2">
                                                            <small class="d-block">
                                                                Wallet
                                                            </small>
                                                            <h6 class="fw-normal mb-0">
                                                                Starbucks
                                                            </h6>
                                                        </div>
                                                        <div class="user-progress d-flex align-items-center gap-2">
                                                            <h6 class="fw-normal mb-0">
                                                                +203.33
                                                            </h6>
                                                            <span class="text-body-secondary">
                                                                USD
                                                            </span>
                                                        </div>
                                                    </div>
                                                </li>
                                                <li class="d-flex align-items-center">
                                                    <div class="avatar flex-shrink-0 me-3">
                                                        <img src="/assets/img/icons/unicons/cc-warning.png" alt="User" class="rounded" />
                                                    </div>
                                                    <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2" >
                                                        <div class="me-2">
                                                            <small class="d-block">
                                                                Mastercard
                                                            </small>
                                                            <h6 class="fw-normal mb-0">
                                                                Ordered Food
                                                            </h6>
                                                        </div>
                                                        <div class="user-progress d-flex align-items-center gap-2">
                                                            <h6 class="fw-normal mb-0">
                                                                -92.45
                                                            </h6>
                                                            <span class="text-body-secondary">
                                                                USD
                                                            </span>
                                                        </div>
                                                    </div>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <!--/ Transactions -->
                            </div>
                        </div>
                        <!-- / Content -->
                        <div class="content-backdrop fade"></div>
                    </div>
                    <!-- Content wrapper -->
                </div>
                <!-- / Layout page -->
            </div>
            <!-- Overlay -->
            <div class="layout-overlay layout-menu-toggle"></div>
            <!-- Drag Target Area To SlideIn Menu On Small Screens -->
            <div class="drag-target" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);" ></div>
        </div>
        <!-- / Layout wrapper -->
        <!-- Page JS -->
        @push ('js')
            <script src="/assets/js/dashboards-analytics.js"></script>
        @endpush
    </div>

@endsection
