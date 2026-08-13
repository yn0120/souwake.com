<?php

namespace App\Http\Controllers\Office;

use App\Enums\PerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Office\WeddingRsvp\EditRequest;
use App\Libraries\Utils;
use App\Models\WeddingRsvpCompanionModel;
use App\Models\WeddingRsvpModel;
use App\Models\WeddingRsvpPhotoModel;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * 結婚式サイト（wedding.souwake.com）から届いた出欠回答（RSVP）の管理。
 *
 * 回答の登録は結婚式サイト側（WeddingRsvpController）でのみ行うため、
 * 管理画面では一覧・詳細・編集・削除のみを扱う。
 */
class OfficeWeddingRsvpsController extends Controller
{
    /**
     * 出欠回答一覧
     *
     * @return View
     */
    public function index(Request $request)
    {
        // フォームで使ったセッションを削除（入力途中でやめた場合を考慮）
        session()->forget(['updateInputWeddingRsvp', 'updateWeddingRsvp', 'updateCompanionsWeddingRsvp']);

        // 各種選択肢
        // 出欠
        $assign['attendances'] = WeddingRsvpModel::attendanceOptions();

        // ご住所の国
        $assign['countries'] = WeddingRsvpModel::countryOptions();

        // 表示件数
        $assign['perPages'] = PerPage::toArray();

        // 出欠回答取得（出欠回答テーブル（wedding_rsvps）を参照し有効なレコード（deleted_at=null）をID降順でソートし表示する。）
        $builder = WeddingRsvpModel::query()
            ->withCount(['companions', 'photos'])
            ->whereNull('deleted_at')
            ->orderBy('id', 'desc');

        // お名前・フリガナのLIKE検索（姓名を結合した文字列でも引っ掛かるようにする）
        if ($request->filled('name')) {
            $name = str_replace(['　', ' '], '', $request->name);
            $builder->where(function ($query) use ($name) {
                $query->whereRaw('CONCAT(name_sei, name_mei) like ?', ["%{$name}%"])
                    ->orWhereRaw('CONCAT(kana_sei, kana_mei) like ?', ["%{$name}%"]);
            });
        }

        // メールアドレスのLIKE検索
        if ($request->filled('email')) {
            $builder->where('email', 'like', "%{$request->email}%");
        }

        // 出欠のIN検索
        if ($request->filled('attendance')) {
            $builder->whereIn('attendance', $request->attendance);
        }

        // ご住所の国のIN検索
        if ($request->filled('country')) {
            $builder->whereIn('country', $request->country);
        }

        // 同伴者の有無検索
        if ($request->filled('companion_flag')) {
            $builder->whereIn('companion_flag', $request->companion_flag);
        }

        // 登録日時の範囲検索
        if ($request->filled('created_at_from')) {
            $builder->where('created_at', '>=', Carbon::parse($request->created_at_from)->startOfDay());
        }
        if ($request->filled('created_at_to')) {
            $builder->where('created_at', '<=', Carbon::parse($request->created_at_to)->endOfDay());
        }

        // 表示件数を取得
        $assign['per_page'] = Utils::perPage($request->get('per_page', PerPage::FIFTY->getLabel()));

        // ページネーションを設定
        $assign['records'] = $builder->paginate($assign['per_page']);

        // 検索条件をビューに渡す
        $assign['input'] = $request->all();

        // 戻るボタン用に検索条件を保管
        session(['officeWeddingRsvpIndexSearchParams' => $assign['input']]);

        return view('office/wedding-rsvps/index', compact('assign'));
    }

    /**
     * 出欠回答詳細
     *
     * @return View
     */
    public function show(Request $request, $id)
    {
        // フォームで使ったセッションを削除（入力途中でやめた場合を考慮）
        session()->forget(['updateInputWeddingRsvp', 'updateWeddingRsvp', 'updateCompanionsWeddingRsvp']);

        // 出欠回答取得（URIのidを基に、出欠回答テーブル（wedding_rsvps）を参照し有効なレコード(deleted_at=null)を表示する。)
        $assign['record'] = WeddingRsvpModel::getBy(['id' => $id, 'method' => 'first']);
        if (! $assign['record']) {
            return redirect()->route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams'))->with('error', '出欠回答が存在しません。');
        }

        $assign['record']->load(['companions', 'photos']);

        return view('office/wedding-rsvps/show', compact('assign'));
    }

    /**
     * 出欠回答編集（入力）
     *
     * @return View
     */
    public function editInput(Request $request, $id)
    {
        // 出欠回答取得
        $assign['record'] = WeddingRsvpModel::getBy(['id' => $id, 'method' => 'first']);
        if (! $assign['record']) {
            return redirect()->route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams'))->with('error', '出欠回答が存在しません。');
        }

        $assign['record']->load('companions');

        // 各種選択肢
        $assign['attendances'] = WeddingRsvpModel::attendanceOptions();
        $assign['countries'] = WeddingRsvpModel::countryOptions();
        $assign['states'] = WeddingRsvpModel::usStates();
        $assign['costumeSizes'] = WeddingRsvpModel::costumeSizeOptions();
        $assign['meals'] = WeddingRsvpCompanionModel::mealOptions();

        // 同伴者の初期値（old()があればそれを優先）
        $assign['companions'] = old('companions', self::companionsToInput($assign['record']));

        return view('office/wedding-rsvps/edit/input', compact('assign'));
    }

    /**
     * 出欠回答編集（確認）
     *
     * @return View
     */
    public function editConfirm(EditRequest $request, $id)
    {
        $input = $request->validated();

        // 出欠回答取得
        $assign['record'] = WeddingRsvpModel::getBy(['id' => $id, 'method' => 'first']);
        if (! $assign['record']) {
            return redirect()->route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams'))->with('error', '出欠回答が存在しません。');
        }

        // 各種選択肢
        $assign['attendances'] = WeddingRsvpModel::attendanceOptions();
        $assign['countries'] = WeddingRsvpModel::countryOptions();
        $assign['costumeSizes'] = WeddingRsvpModel::costumeSizeOptions();
        $assign['meals'] = WeddingRsvpCompanionModel::mealOptions();

        // 同伴者は別テーブルへ保存するため、回答本体の入力値から切り離す
        $companions = $input['companions'] ?? [];
        unset($input['companions']);

        // 「同伴者なし」の場合は行が送られてきても登録しない（結婚式サイトのフォームと同じ扱い）
        if ($input['companion_flag'] === '0') {
            $companions = [];
        }

        // ご欠席の場合は当日のご予定に関する項目を保持しない（結婚式サイトのフォームと同じ扱い）
        if ($input['attendance'] === WeddingRsvpModel::ATTENDANCE_ABSENT) {
            $input['allergy'] = null;
            $input['arrival_date'] = null;
            $input['departure_date'] = null;
            $input['hotel_name'] = null;
            $input['costume_size'] = null;
        }

        // 確認ページ表示用に加工、更新用に加工
        $update = [];
        foreach ($input as $key => $value) {
            switch ($key) {
                case 'attendance':
                    $assign['confirm'][$key] = $assign['attendances'][$value] ?? null;
                    $update[$key] = $value;
                    break;

                case 'country':
                    $assign['confirm'][$key] = $assign['countries'][$value] ?? null;
                    $update[$key] = $value;
                    break;

                case 'kana_sei':
                case 'kana_mei':
                    // アメリカ在住の方はフリガナが任意のため未入力で届く。カラムはNOT NULLなので空文字で保存する
                    $assign['confirm'][$key] = $value;
                    $update[$key] = $value ?? '';
                    break;

                case 'arrival_date':
                case 'departure_date':
                    $assign['confirm'][$key] = Utils::dateToYmdJa($value);
                    $update[$key] = $value ? date('Y-m-d', strtotime($value)) : null;
                    break;

                case 'companion_flag':
                    $assign['confirm'][$key] = $value === '1' ? 'あり' : 'なし';
                    $update[$key] = $value === '1';
                    break;

                default:
                    $assign['confirm'][$key] = $value;
                    $update[$key] = $value;
                    break;
            }
        }

        // 同伴者は連名（複数）のため、確認ページ表示用に1名ずつ整える
        $assign['confirmCompanions'] = [];
        foreach (array_values($companions) as $companion) {
            $assign['confirmCompanions'][] = [
                'name' => trim("{$companion['name_sei']} {$companion['name_mei']}"),
                'kana' => trim(($companion['kana_sei'] ?? '').' '.($companion['kana_mei'] ?? '')),
                'meal' => $assign['meals'][$companion['meal'] ?? ''] ?? '未選択',
                'child_info' => $companion['child_info'] ?? null,
            ];
        }

        session(['updateInputWeddingRsvp' => $request->validated(), 'updateWeddingRsvp' => $update, 'updateCompanionsWeddingRsvp' => array_values($companions)]);

        return view('office/wedding-rsvps/edit/confirm', compact('assign'));
    }

    /**
     * 出欠回答編集（処理）
     *
     * @return RedirectResponse
     */
    public function editExecute(Request $request, $id)
    {
        // 書き直し処理
        if ($request->back) {
            return redirect()->route('officeWeddingRsvpEditInput', ['id' => $id])->withInput(session('updateInputWeddingRsvp'));
        }

        // 出欠回答取得
        $record = WeddingRsvpModel::getBy(['id' => $id, 'method' => 'first']);
        if (! $record) {
            return redirect()->route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams'))->with('error', '出欠回答が存在しません。');
        }

        $request->session()->regenerateToken();

        // update用入力値
        $update = session('updateWeddingRsvp');
        $companions = session('updateCompanionsWeddingRsvp', []);
        if (! $update) {
            return redirect()->route('officeWeddingRsvpEditInput', ['id' => $id])->with('error', '入力内容が取得できませんでした。お手数ですが再度ご入力ください。');
        }

        // 同伴者は連名（wedding_rsvp_companions）へ移行済みのため、移行前の旧カラムは編集時にクリアする
        $update['companion_name'] = null;
        $update['companion_kana'] = null;
        $update['companion_meal'] = null;
        $update['child_info'] = null;

        try {
            DB::beginTransaction();

            // 更新
            WeddingRsvpModel::where('id', $id)->whereNull('deleted_at')->update($update);

            // 同伴者を画面の内容に合わせる（既存行は更新、新規行は登録、画面から消された行は削除）
            $keepIds = [];
            foreach ($companions as $sortNo => $companion) {
                $values = [
                    'sort_no' => $sortNo,
                    'name_sei' => $companion['name_sei'],
                    'name_mei' => $companion['name_mei'],
                    'kana_sei' => $companion['kana_sei'] ?? '',
                    'kana_mei' => $companion['kana_mei'] ?? '',
                    'meal' => $companion['meal'] ?? null,
                    'child_info' => $companion['child_info'] ?? null,
                ];

                $companionId = $companion['id'] ?? null;
                $exists = $companionId && WeddingRsvpCompanionModel::where('id', $companionId)->where('wedding_rsvp_id', $id)->exists();
                if ($exists) {
                    WeddingRsvpCompanionModel::where('id', $companionId)->update($values);
                    $keepIds[] = (int) $companionId;

                    continue;
                }

                $keepIds[] = WeddingRsvpCompanionModel::create($values + ['wedding_rsvp_id' => $id])->id;
            }

            $deleteBuilder = WeddingRsvpCompanionModel::where('wedding_rsvp_id', $id);
            if ($keepIds) {
                $deleteBuilder->whereNotIn('id', $keepIds);
            }
            $deleteBuilder->delete();

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '出欠回答編集（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeWeddingRsvpEditInput', ['id' => $id])->withInput(session('updateInputWeddingRsvp'))->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '出欠回答編集（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeWeddingRsvpEditInput', ['id' => $id])->withInput(session('updateInputWeddingRsvp'))->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeWeddingRsvpEditComplete', ['id' => $id]);
    }

    /**
     * 出欠回答編集（完了）
     *
     * @return View
     */
    public function editComplete(Request $request, $id)
    {
        // フォームで使ったセッションを削除
        session()->forget(['updateInputWeddingRsvp', 'updateWeddingRsvp', 'updateCompanionsWeddingRsvp']);

        $assign['id'] = $id;

        return view('office/wedding-rsvps/edit/complete', compact('assign'));
    }

    /**
     * 出欠回答削除（処理）
     *
     * @return RedirectResponse
     */
    public function deleteExecute(Request $request, $id)
    {
        $request->session()->regenerateToken();

        // 出欠回答取得
        $record = WeddingRsvpModel::getBy(['id' => $id, 'method' => 'first']);
        if (! $record) {
            return redirect()->route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams'))->with('error', '出欠回答が存在しません。');
        }

        try {
            DB::beginTransaction();

            // 削除（同伴者・お祝い画像は復元できるよう残し、回答のみ論理削除する）
            WeddingRsvpModel::where('id', $id)->update(['deleted_at' => Carbon::now()]);

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '出欠回答削除（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams'))->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '出欠回答削除（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams'))->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeWeddingRsvpIndex', session('officeWeddingRsvpIndexSearchParams'))->with('success', '削除しました。');
    }

    /**
     * お祝い画像表示
     *
     * 結婚式サイト側の公開エンドポイントに依存せず、管理画面のログイン・権限の内側で画像を配信する。
     *
     * @return BinaryFileResponse
     */
    public function photoShow(Request $request, $id, $uuid)
    {
        // uuidの形式チェック（パストラバーサル・不正値の混入防止）
        abort_unless(is_string($uuid) && preg_match('/^[0-9a-fA-F-]{36}$/', $uuid) === 1, 404);

        /** @var WeddingRsvpPhotoModel|null $photo */
        $photo = WeddingRsvpPhotoModel::getBy(['uuid' => $uuid, 'wedding_rsvp_id' => $id, 'method' => 'first']);
        abort_if(! $photo || $photo->status === 'failed', 404);

        $disk = Storage::disk('wedding_photos');

        // 変換完了前はアップロードされた原本をそのまま返す
        $path = $photo->stored_path ?: $photo->staging_path;
        abort_if(! $path || ! $disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $photo->stored_path ? $photo->mime_type : ($disk->mimeType($path) ?: 'application/octet-stream'),
            'Content-Disposition' => 'inline; filename="'.addslashes($photo->original_name).'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    /**
     * 編集フォームの初期値に使う同伴者の配列を返す
     *
     * 連名（wedding_rsvp_companions）へ移行する前の回答は旧カラムにしか同伴者が入っていないため、
     * 行が無く旧カラムに値がある場合は1名分の入力行として引き継ぐ。
     *
     * @return array<int, array<string, mixed>>
     */
    private static function companionsToInput(WeddingRsvpModel $record): array
    {
        $companions = $record->companions->map(fn (WeddingRsvpCompanionModel $companion) => [
            'id' => $companion->id,
            'name_sei' => $companion->name_sei,
            'name_mei' => $companion->name_mei,
            'kana_sei' => $companion->kana_sei,
            'kana_mei' => $companion->kana_mei,
            'meal' => $companion->meal,
            'child_info' => $companion->child_info,
        ])->all();

        if ($companions || ! $record->companion_name) {
            return $companions;
        }

        // 旧カラムは姓名が分かれていないため、姓の欄へまとめて引き継ぐ
        return [[
            'id' => null,
            'name_sei' => $record->companion_name,
            'name_mei' => '',
            'kana_sei' => $record->companion_kana,
            'kana_mei' => '',
            'meal' => $record->companion_meal,
            'child_info' => $record->child_info,
        ]];
    }
}
