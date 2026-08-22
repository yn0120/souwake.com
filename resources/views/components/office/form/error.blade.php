{{--
    バリデーションエラー。name には 'email' や 'companions.0.name_sei' のようなキー、
    複数まとめたい時は配列を渡す。
--}}
@props(['name'])

@foreach ((array) $name as $key)
    @error ($key)
        <p role="alert" class="mt-1 rounded-lg border border-danger-subtle bg-danger-soft px-2 py-1 text-xs break-words text-danger-strong">
            {{ $message }}
        </p>
    @enderror
@endforeach
