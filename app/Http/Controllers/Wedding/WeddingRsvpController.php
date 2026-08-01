<?php

namespace App\Http\Controllers\Wedding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wedding\RsvpCreateRequest;
use App\Libraries\Utils;
use App\Mail\Wedding\RsvpAdminMail;
use App\Mail\Wedding\RsvpGuestMail;
use App\Models\WeddingRsvpModel;
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
        $input['companion_flag'] = $input['companion_flag'] === '1';
        if (! $input['companion_flag']) {
            $input['companion_name'] = null;
            $input['companion_kana'] = null;
            $input['companion_meal'] = null;
            $input['child_info'] = null;
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
