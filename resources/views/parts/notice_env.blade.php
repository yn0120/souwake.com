@if (config('app.env') !== 'production')
    @switch (config('app.env_domain'))
        @case ('stg.')
            stg環境で送信されました。<br><br>
            @break
        @case ('dev.')
            dev環境で送信されました。<br><br>
            @break
        @default
            local環境で送信されました。<br><br>
            @break
    @endswitch
@endif
