@include ('parts/notice_env')

{{ $assign['admin']->name }} 様<br><br>

ログイン用のワンタイムキーを発行しました。<br>
下記のワンタイムキーを入力すると、スキャンテスト管理システムへログインできます。<br><br>

→ {{ $assign['onetimeKey'] }}<br><br><br><br>
