<?php

namespace App\Http\Controllers\Wedding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wedding\RsvpCreateRequest;
use App\Libraries\Utils;
use App\Mail\Wedding\RsvpAdminMail;
use App\Mail\Wedding\RsvpGuestMail;
use App\Models\WeddingRsvpCompanionModel;
use App\Models\WeddingRsvpModel;
use App\Models\WeddingRsvpPhotoModel;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class WeddingRsvpController extends Controller
{
    /**
     * 招待ページ（フォーム含む）
     *
     * @return View
     */
    public function index()
    {
        return view('wedding/index');
    }

    /**
     * 出欠回答（処理）
     *
     * @return RedirectResponse
     */
    public function createExecute(RsvpCreateRequest $request)
    {
        $input = $request->validated();
        unset($input['contact_note']);

        // お祝い画像は送信前に非同期アップロード済み。uuidと元ブラウザのトークンで本人の画像だけを紐づける。
        $photoTokens = array_values(array_unique($input['photo_tokens'] ?? []));
        $photoSessionToken = $input['photo_session_token'] ?? null;
        unset($input['photo_tokens'], $input['photo_session_token']);

        // アメリカ在住の方はフリガナが任意のため未入力で届く。カラムはNOT NULLなので空文字で保存する
        $input['kana_sei'] ??= '';
        $input['kana_mei'] ??= '';

        // アメリカの住所は州をprefectureカラムへ寄せ、日本の都道府県と同じ位置に保存する
        $state = $input['state'] ?? null;
        unset($input['state']);
        if ($input['country'] === WeddingRsvpModel::COUNTRY_US) {
            $input['prefecture'] = $state;
        }

        // 同伴者は連名（複数）で受け取り、別テーブルへ保存する
        $companions = $input['companions'] ?? [];
        unset($input['companions']);

        $input['companion_flag'] = $input['companion_flag'] === '1';
        if (! $input['companion_flag']) {
            $companions = [];
        }
        if ($input['attendance'] === 'absent') {
            $input['allergy'] = null;
            $input['arrival_date'] = null;
            $input['departure_date'] = null;
            $input['hotel_name'] = null;
            $input['costume_size'] = null;
        }

        try {
            DB::beginTransaction();

            $rsvp = WeddingRsvpModel::create($input);

            foreach (array_values($companions) as $sortNo => $companion) {
                WeddingRsvpCompanionModel::create([
                    'wedding_rsvp_id' => $rsvp->id,
                    'sort_no' => $sortNo,
                    'name_sei' => $companion['name_sei'],
                    'name_mei' => $companion['name_mei'],
                    'kana_sei' => $companion['kana_sei'] ?? '',
                    'kana_mei' => $companion['kana_mei'] ?? '',
                    'meal' => $companion['meal'] ?? null,
                    'child_info' => $companion['child_info'] ?? null,
                ]);
            }

            if ($photoTokens && $photoSessionToken) {
                WeddingRsvpPhotoModel::whereIn('uuid', $photoTokens)
                    ->where('session_token', $photoSessionToken)
                    ->whereNull('wedding_rsvp_id')
                    ->update(['wedding_rsvp_id' => $rsvp->id]);
            }
            $rsvp->load(['photos', 'companions']);

            $subject = '【ご回答ありがとうございました】ご出欠のお控え';
            Mail::to($rsvp->email)->queue(new RsvpGuestMail($subject, ['assign' => ['rsvp' => $rsvp]]));

            $adminEmail = config('services.wedding.admin_email') ?: config('mail.from.address');
            Mail::to($adminEmail)->queue(new RsvpAdminMail('【結婚式サイト】新しいご回答が届きました', ['assign' => ['rsvp' => $rsvp]]));

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '結婚式RSVP登録（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('weddingRsvpInput')->withInput($request->except('contact_note'))->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '結婚式RSVP登録（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('weddingRsvpInput')->withInput($request->except('contact_note'))->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('weddingRsvpComplete');
    }

    /**
     * 出欠回答 完了画面
     *
     * @return View
     */
    public function complete()
    {
        return view('wedding/complete');
    }
}
