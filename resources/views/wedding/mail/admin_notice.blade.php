@include ('parts/notice_env')

結婚式サイトへ新しいご回答が届きました。<br><br>

―――――――――――――――――――<br>
【ご回答内容】<br>
―――――――――――――――――――<br>
出欠：{{ $assign['rsvp']->attendance === 'attending' ? 'ご出席' : 'ご欠席' }}<br>
お名前：{{ $assign['rsvp']->name_sei }} {{ $assign['rsvp']->name_mei }}（{{ $assign['rsvp']->kana_sei }} {{ $assign['rsvp']->kana_mei }}）<br>
ご住所：〒{{ $assign['rsvp']->postal_code }} {{ $assign['rsvp']->prefecture }}{{ $assign['rsvp']->city }}{{ $assign['rsvp']->address }} {{ $assign['rsvp']->building }}<br>
電話番号：{{ $assign['rsvp']->phone }}<br>
メールアドレス：{{ $assign['rsvp']->email }}<br>
アレルギー・お食事のご要望：{{ $assign['rsvp']->allergy ?: 'なし' }}<br>
到着日：{{ optional($assign['rsvp']->arrival_date)->format('Y年m月d日') ?: '未定' }}<br>
出発日：{{ optional($assign['rsvp']->departure_date)->format('Y年m月d日') ?: '未定' }}<br>
宿泊先ホテル：{{ $assign['rsvp']->hotel_name ?: '未定' }}<br>
衣装サイズ：{{ $assign['rsvp']->costume_size ?: '選択なし' }}<br>
同伴者：{{ $assign['rsvp']->companion_flag ? 'あり' : 'なし' }}<br>
@if ($assign['rsvp']->companion_flag)
    同伴者お名前：{{ $assign['rsvp']->companion_name }}（{{ $assign['rsvp']->companion_kana }}）<br>
    同伴者のお食事：
    @switch ($assign['rsvp']->companion_meal)
        @case ('adult')
            大人メニュー<br>
            @break
        @case ('child_lunch')
            お子様ランチ<br>
            @break
        @case ('child_plate')
            お子様プレート<br>
            @break
        @default
            不要<br>
    @endswitch
    お子様連れの追加情報：{{ $assign['rsvp']->child_info ?: 'なし' }}<br>
@endif
メッセージ：{{ $assign['rsvp']->message ?: 'なし' }}<br>
楽曲リクエスト：{{ $assign['rsvp']->song_request ?: 'なし' }}<br>
―――――――――――――――――――<br><br>

登録日時：{{ $assign['rsvp']->created_at->format('Y年m月d日 H:i') }}<br>
