/*
 * 非公開ファイルのアップロード。
 *
 * Dropzoneはチャンク送信の設定が肝で、少しでも触ると大きいファイルが黙って落ちる。
 * CSSフレームワークの移行では設定・イベントの流れを変えないこと（挙動はそのまま移設した）。
 * Dropzone本体は public/assets/vendor/libs/dropzone を <script> で先に読み込んでいる。
 */
(function () {
    'use strict';

    var formEl = document.getElementById('secrets-dropzone');
    var submitEl = document.getElementById('secrets-upload-submit');
    if (! formEl || ! submitEl || typeof Dropzone === 'undefined') {
        return;
    }

    Dropzone.autoDiscover = false;

    var secretsDropzone = new Dropzone('#secrets-dropzone', {
        url: formEl.getAttribute('action'),
        paramName: 'file',
        acceptedFiles: 'image/*,video/*',
        autoProcessQueue: false,
        uploadMultiple: false,
        parallelUploads: 1,
        parallelChunkUploads: false,
        chunking: true,
        forceChunking: true,
        chunkSize: 10 * 1024 * 1024,
        maxFilesize: 3200, // MB
        timeout: 0,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
    });

    submitEl.addEventListener('click', function () {
        if (secretsDropzone.getQueuedFiles().length === 0) {
            return;
        }
        secretsDropzone.processQueue();
    });

    // parallelUploads:1 + autoProcessQueue:false のため、1件完了しても次のファイルへは自動で進まない。
    // 完了のたびに残りキューがあれば再度processQueue()を呼び、複数ファイルを順番に送り切る。
    secretsDropzone.on('complete', function () {
        if (secretsDropzone.getQueuedFiles().length > 0) {
            secretsDropzone.processQueue();
        }
    });

    secretsDropzone.on('queuecomplete', function () {
        if (secretsDropzone.files.length > 0) {
            window.location.reload();
        }
    });
})();
