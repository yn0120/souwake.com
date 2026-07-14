@extends('office/parts/app')

@section('content')

    <div class="container-xxl container-p-y">
        @include ('office/parts/item/alert')

        <div class="card p-5">
            <div class="table-responsive text-nowrap">
                <table class="table table-bordered">
                    <thead>
                        <tr class="bg-black">
                            <th class="text-white fw-bold">ファイル名</th>
                            <th class="text-white fw-bold">登録日時</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assign['records'] as $record)
                            <tr>
                                <td>
                                    <a href="{{ route('officeSecretsView', $record->id, false) }}" target="_blank" rel="noopener">
                                        {{ $record->original_name }}
                                    </a>
                                </td>
                                <td>{{ $record->created_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center">秘密ファイルはありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $assign['records']->links('office/parts/item/pagination') }}
        </div>
    </div>

@endsection
