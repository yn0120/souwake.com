@extends('office/parts/app')

@push('css')
    <link rel="stylesheet" href="/assets/vendor/libs/dropzone/dropzone.css">
@endpush

@section('content')

    <div class="container-xxl container-p-y">
        @include ('office/parts/item/alert')

        <div class="card">
            <div class="card-body">
                <form action="{{ route('officeSecretsUploadChunk', [], false) }}" class="dropzone needsclick dz-clickable" id="secrets-dropzone"></form>
                <div class="mt-4 text-end">
                    <button type="button" id="secrets-upload-submit" class="btn btn-primary">アップロード</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push ('js')
    <script src="/assets/vendor/libs/dropzone/dropzone.js"></script>
    <script>
        Dropzone.autoDiscover = false;

        const secretsDropzone = new Dropzone('#secrets-dropzone', {
            url: document.getElementById('secrets-dropzone').getAttribute('action'),
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

        document.getElementById('secrets-upload-submit').addEventListener('click', function () {
            if (secretsDropzone.getQueuedFiles().length === 0) {
                return;
            }
            secretsDropzone.processQueue();
        });

        secretsDropzone.on('queuecomplete', function () {
            if (secretsDropzone.files.length > 0) {
                // window.location.reload();
            }
        });
    </script>
@endpush
