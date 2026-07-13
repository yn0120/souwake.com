@include ('parts/notice_env')

{{ $assign['admin']->name }} 様<br><br>

パスワードの設定依頼を受け付けました。<br>
※パスワードの設定はまだ完了しておりません。<br><br>

以下のURLより、新しいパスワードの設定へお進みください。<br>
URLは72時間をすぎると無効となりますのでご注意ください。<br>
→ <a href="{{ $assign['url'] }}" target="_blank">{{ $assign['url'] }}</a><br><br><br><br>
