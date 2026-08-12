@include ('parts/notice_env')

結婚式サイトへ新しいご回答が届きました。<br><br>

―――――――――――――――――――<br>
【ご回答内容】<br>
―――――――――――――――――――<br>
出欠：{{ $assign['rsvp']->attendance === 'attending' ? 'ご出席' : 'ご欠席' }}<br>
お名前：{{ $assign['rsvp']->fullName() }}<br>
お住まいの国：{{ App\Models\WeddingRsvpModel::countryOptions()[$assign['rsvp']->country] ?? $assign['rsvp']->country }}<br>
ご住所：{{ $assign['rsvp']->fullAddress() }}<br>
電話番号：{{ $assign['rsvp']->phone }}<br>
メールアドレス：{{ $assign['rsvp']->email }}<br>
アレルギー・お食事のご要望：{{ $assign['rsvp']->allergy ?: 'なし' }}<br>
到着日：{{ optional($assign['rsvp']->arrival_date)->format('Y年m月d日') ?: '未定' }}<br>
出発日：{{ optional($assign['rsvp']->departure_date)->format('Y年m月d日') ?: '未定' }}<br>
宿泊先ホテル：{{ $assign['rsvp']->hotel_name ?: '未定' }}<br>
衣装サイズ：{{ $assign['rsvp']->costume_size ?: '選択なし' }}<br>
同伴者：{{ $assign['rsvp']->companions->isEmpty() ? 'なし' : 'あり（'.$assign['rsvp']->companions->count().'名）' }}<br>
@foreach ($assign['rsvp']->companions as $companion)
    ―――――<br>
    【同伴者{{ $loop->iteration }}名目】<br>
    お名前：{{ $companion->fullName() }}<br>
    お食事：{{ $companion->mealLabel() }}<br>
    お子様連れの追加情報：{{ $companion->child_info ?: 'なし' }}<br>
@endforeach
@if ($assign['rsvp']->companions->isNotEmpty())
    ―――――<br>
@endif
メッセージ：{{ $assign['rsvp']->message ?: 'なし' }}<br>
楽曲リクエスト：{{ $assign['rsvp']->song_request ?: 'なし' }}<br>
お祝い画像：{{ $assign['rsvp']->photos->isEmpty() ? 'なし' : $assign['rsvp']->photos->count().'枚' }}<br>
@foreach ($assign['rsvp']->photos as $photo)
    ・<a href="{{ route('weddingRsvpPhotoShow', ['uuid' => $photo->uuid]) }}">{{ $photo->original_name }}</a><br>
@endforeach
―――――――――――――――――――<br><br>

登録日時：{{ $assign['rsvp']->created_at->format('Y年m月d日 H:i') }}<br>
