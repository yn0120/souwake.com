@include ('parts/notice_env')

{{ $assign['admin']['name'] }} 様<br><br>

パスワードの変更が完了しました。<br>
※パスワードはログイン時に必要になるため、ご自身で大切に管理していただくようお願いいたします。<br><br>

スキャンテストポータルURL：<br>
→ <a href="{{ route('officeTop', [], false) }}" target="_blank">{{ route('officeTop') }}</a><br><br><br>


パスワードを変更した覚えがない場合は、下記のURLよりパスワードの設定をお願いいたします。<br>
→ <a href="{{ route('officeForgotPwInput', [], false) }}" target="_blank">{{ route('officeForgotPwInput') }}</a><br><br><br><br>
