{{--
    ダッシュボード。

    中身はテンプレート同梱のサンプル（Transactions）をそのまま移してある。
    実データを出す時はこのカードを置き換える。
--}}
<x-office.layout>
    @php
        $transactions = [
            ['icon' => 'paypal', 'label' => 'Paypal', 'name' => 'Send money', 'amount' => '+82.6'],
            ['icon' => 'wallet', 'label' => 'Wallet', 'name' => "Mac'D", 'amount' => '+270.69'],
            ['icon' => 'chart', 'label' => 'Transfer', 'name' => 'Refund', 'amount' => '+637.91'],
            ['icon' => 'cc-primary', 'label' => 'Credit Card', 'name' => 'Ordered Food', 'amount' => '-838.71'],
            ['icon' => 'wallet', 'label' => 'Wallet', 'name' => 'Starbucks', 'amount' => '+203.33'],
            ['icon' => 'cc-warning', 'label' => 'Mastercard', 'name' => 'Ordered Food', 'amount' => '-92.45'],
        ];
    @endphp

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <x-office.card>
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-heading">Transactions</h2>

                {{-- ドロップダウン。開閉はFlowbiteの data-dropdown-toggle に任せる。 --}}
                <button type="button" id="transactionID" data-dropdown-toggle="transactionMenu"
                        aria-label="表示期間を変更する"
                        class="cursor-pointer rounded-lg p-1.5 text-body transition-colors hover:bg-neutral-tertiary hover:text-heading">
                    <x-office.icon name="chevron-down" class="size-4" />
                </button>
                <div id="transactionMenu"
                     class="z-10 hidden w-40 rounded-lg border border-default bg-white py-1 shadow-lg">
                    <ul class="text-sm text-body" aria-labelledby="transactionID">
                        @foreach (['Last 28 Days', 'Last Month', 'Last Year'] as $range)
                            <li>
                                <a href="javascript:void(0);" class="block px-4 py-2 hover:bg-neutral-secondary hover:text-heading">{{ $range }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <ul class="space-y-5">
                @foreach ($transactions as $transaction)
                    <li class="flex items-center gap-3">
                        <img src="/assets/img/icons/unicons/{{ $transaction['icon'] }}.png" alt=""
                             class="size-10 shrink-0 rounded" />
                        <div class="flex w-full flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="block text-xs text-body">{{ $transaction['label'] }}</span>
                                <span class="text-sm text-heading">{{ $transaction['name'] }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-heading">{{ $transaction['amount'] }}</span>
                                <span class="text-xs text-body">USD</span>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-office.card>
    </div>
</x-office.layout>
