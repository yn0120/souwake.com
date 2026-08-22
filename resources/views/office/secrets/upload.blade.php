{{--
    非公開ファイルのアップロード。

    Dropzone（チャンク送信）はCSSフレームワークとは独立したライブラリなので、
    動作実績のある public/assets/vendor/libs/dropzone をそのまま使い続けている。
--}}
<x-office.plain-layout title="アップロード">
    <x-slot:head>
        <link rel="stylesheet" href="/assets/vendor/libs/dropzone/dropzone.css">
    </x-slot:head>

    <form action="{{ route('officeSecretsUploadChunk', [], false) }}"
          class="dropzone needsclick dz-clickable rounded-lg border-2 border-dashed border-default"
          id="secrets-dropzone"></form>

    <div class="mt-4 text-right">
        <x-office.button variant="primary" id="secrets-upload-submit">アップロード</x-office.button>
    </div>

    <x-slot:scripts>
        <script src="/assets/vendor/libs/dropzone/dropzone.js"></script>
        @vite('resources/js/office/secrets-upload.js')
    </x-slot:scripts>
</x-office.plain-layout>
