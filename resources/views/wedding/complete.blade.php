@extends('wedding/layout')

@section('title', 'ご回答ありがとうございました | Wedding Invitation')

@section('content')
    {{-- 送信が完了したので、localStorageに退避していた入力内容・画像情報を破棄する（wedding.js） --}}
    <section class="flex min-h-screen flex-col items-center justify-center px-6 text-center" data-clear-rsvp-storage>
        <p class="text-xs tracking-[0.35em] text-moss-600">THANK YOU</p>
        <h1 class="mt-4 font-serif-jp text-2xl text-ink-800 sm:text-3xl">ご回答ありがとうございました</h1>
        <p class="mt-6 max-w-md leading-loose text-ink-700">
            ご入力いただいたメールアドレス宛に、ご回答内容の控えをお送りしております。<br>
            届いていない場合は、お手数ですが迷惑メールフォルダもご確認いただけますと幸いです。
        </p>
        <p class="mt-8 text-sm text-ink-700/70">
            回答内容の修正がございましたら、お手数ですが控えメールへの返信にてお知らせください。
        </p>
        <a href="{{ route('weddingRsvpInput') }}" class="mt-10 inline-flex items-center rounded-full border border-moss-600 px-6 py-2.5 text-sm text-moss-700 transition hover:bg-moss-600 hover:text-sand-50">
            招待ページに戻る
        </a>
    </section>
@endsection
