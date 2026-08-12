@include ('parts/notice_env')

{{ $assign['rsvp']->name_sei }} {{ $assign['rsvp']->name_mei }} 様<br><br>

この度はご回答いただき、誠にありがとうございます。<br>
下記の内容で承りましたので、控えとしてお送りいたします。<br><br>

@if ($assign['rsvp']->attendance === 'attending')
    ご出席とのご回答をいただきました。当日お会いできますことを、心より楽しみにしております。<br><br>
@else
    ご欠席とのご回答をいただきました。またお会いできる日を楽しみにしております。<br><br>
@endif

―――――――――――――――――――<br>
【ご回答内容】<br>
―――――――――――――――――――<br>
出欠：{{ $assign['rsvp']->attendance === 'attending' ? 'ご出席' : 'ご欠席' }}<br>
お名前：{{ $assign['rsvp']->fullName() }}<br>
ご住所：{{ $assign['rsvp']->fullAddress() }}<br>
電話番号：{{ $assign['rsvp']->phone }}<br>
メールアドレス：{{ $assign['rsvp']->email }}<br>
@if ($assign['rsvp']->attendance === 'attending')
    アレルギー・お食事のご要望：{{ $assign['rsvp']->allergy ?: 'なし' }}<br>
    到着日：{{ optional($assign['rsvp']->arrival_date)->format('Y年m月d日') ?: '未定' }}<br>
    出発日：{{ optional($assign['rsvp']->departure_date)->format('Y年m月d日') ?: '未定' }}<br>
    宿泊先ホテル：{{ $assign['rsvp']->hotel_name ?: '未定' }}<br>
    衣装サイズ：{{ $assign['rsvp']->costume_size ?: '選択なし' }}<br>
    同伴者：{{ $assign['rsvp']->companions->isEmpty() ? 'なし' : $assign['rsvp']->companions->count().'名' }}<br>
    @foreach ($assign['rsvp']->companions as $companion)
        　{{ $loop->iteration }}. {{ $companion->fullName() }} 様／お食事：{{ $companion->mealLabel() }}<br>
    @endforeach
@endif
@if ($assign['rsvp']->message)
    メッセージ：{{ $assign['rsvp']->message }}<br>
@endif
@if ($assign['rsvp']->photos->isNotEmpty())
    お祝い画像：{{ $assign['rsvp']->photos->count() }}枚をお預かりしました<br>
@endif
―――――――――――――――――――<br><br>

回答内容に修正がある場合は、本メールに返信の上お知らせください。<br><br>

{{ config('app.name') }}<br>
