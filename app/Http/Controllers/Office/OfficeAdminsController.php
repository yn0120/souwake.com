<?php

namespace App\Http\Controllers\Office;

use App\Enums\PerPage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Office\Admin\CreateRequest;
use App\Http\Requests\Office\Admin\EditRequest;
use App\Libraries\Utils;
use App\Mail\Office\AdminCreateMail;
use App\Models\RoleModel;
use App\Services\EmailService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class OfficeAdminsController extends Controller
{
    /**
     * 管理者一覧
     *
     * @return View
     */
    public function index(Request $request)
    {
        // フォームで使ったセッションを削除（入力途中でやめた場合を考慮）
        session()->forget(['createInputAdmin', 'insertAdmin', 'updateInputAdmin', 'updateAdmin']);

        // 各種選択肢
        // 権限
        $assign['roles'] = RoleModel::getBy(['method' => 'pluck']);

        // 表示件数
        $assign['perPages'] = PerPage::toArray();

        // 管理者取得（管理者テーブル（admins）を参照し有効なレコード（deleted_at=null）をID降順でソートし表示する。）
        $builder = DB::table('admins')->whereNull('deleted_at')->orderBy('id', 'desc');

        // 氏名のLIKE検索
        if ($request->filled('name')) {
            $builder->where('name', 'like', "%{$request->name}%");
        }

        // 権限のIN検索
        if ($request->filled('role_id')) {
            $builder->whereIn('role_id', $request->role_id);
        }

        // メールアドレスのLIKE検索
        if ($request->filled('email')) {
            $builder->where('email', 'like', "%{$request->email}%");
        }

        // 管理者利用状況検索
        if ($request->filled('statuses')) {
            /*
                新規登録した管理者 : created_at IS NOT NULL
                有効中の管理者 : activated_at IS NOT NULL
                退職済みの管理者 : terminated_at IS NOT NULL
                削除済みの管理者 : deleted_at IS NOT NULL
            */
            $statuses = $request->statuses;

            $builder->where(function ($query) use ($statuses) {
                // 初期登録中の管理者 : activated_at IS NULL
                if (in_array('is_activated', $statuses)) {
                    $query->orWhereNull('activated_at');
                }
                // 解約済みの管理者 : terminated_at IS NOT NULL
                if (in_array('is_terminated', $statuses)) {
                    $query->orWhereNotNull('terminated_at');
                }
            });
        }

        // 表示件数を取得
        $assign['per_page'] = Utils::perPage($request->get('per_page', PerPage::FIFTY->getLabel()));

        // ページネーションを設定
        $assign['records'] = $builder->paginate($assign['per_page']);

        // 検索条件をビューに渡す
        $assign['input'] = $request->all();

        // 戻るボタン用に検索条件を保管
        session(['officeAdminIndexSearchParams' => $assign['input']]);

        return view('office/admins/index', compact('assign'));
    }

    /**
     * 管理者詳細
     *
     * @return View
     */
    public function show(Request $request, $id)
    {
        // フォームで使ったセッションを削除（入力途中でやめた場合を考慮）
        session()->forget(['createInputAdmin', 'insertAdmin', 'updateInputAdmin', 'updateAdmin']);

        // 管理者取得（URIのidを基に、管理者テーブル（admins）を参照し有効なレコード(deleted_at=null)を表示する。)
        $assign['record'] = DB::table('admins')->where('id', $id)->whereNull('deleted_at')->first();
        if (! $assign['record']) {
            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', '管理者が存在しません。');
        }

        // 権限
        $assign['roles'] = RoleModel::getBy(['method' => 'pluck']);

        return view('office/admins/show', compact('assign'));
    }

    /**
     * 管理者登録（入力）
     *
     * @return View
     */
    public function createInput(Request $request)
    {
        // 権限
        $assign['roles'] = RoleModel::getBy(['method' => 'pluck']);

        return view('office/admins/create/input', compact('assign'));
    }

    /**
     * 管理者登録（確認）
     *
     * @return View
     */
    public function createConfirm(CreateRequest $request)
    {
        $input = $request->validated();

        // レコード存在チェック
        $exists = DB::table('admins')->where('email', $request->email)->whereNull('deleted_at')->exists();
        if ($exists) {
            return redirect()->route('officeAdminCreateInput')->withInput($input)->withErrors(['email' => '既に同じメールアドレスが登録済みです。同じメールアドレスを登録したい場合は、そのメールアドレスを持つデータを削除してください。']);
        }

        // 権限
        $assign['roles'] = RoleModel::getBy(['method' => 'pluck']);

        // 確認ページ表示用に加工、登録用に加工
        $insert = [];
        foreach ($input as $key => $value) {
            switch ($key) {
                case 'role_id':
                    $assign['confirm'][$key] = $assign['roles'][$value] ?? null;
                    $insert[$key] = $value;
                    break;

                default:
                    $assign['confirm'][$key] = $value;
                    $insert[$key] = $value;
                    break;
            }
        }

        session(['createInputAdmin' => $input, 'insertAdmin' => $insert]);

        return view('office/admins/create/confirm', compact('assign'));
    }

    /**
     * 管理者登録（処理）
     *
     * @return RedirectResponse
     */
    public static function createExecute(Request $request)
    {
        // 書き直し処理
        if ($request->back) {
            return redirect()->route('officeAdminCreateInput')->withInput(session('createInputAdmin'));
        }

        // レコード存在チェック
        $exists = DB::table('admins')->where('email', session('createInputAdmin.email'))->whereNull('deleted_at')->exists();
        if ($exists) {
            return redirect()->route('officeAdminCreateInput')->withInput(session('createInputAdmin'))->withErrors(['email' => '既に同じメールアドレスが登録済みです。同じメールアドレスを登録したい場合は、そのメールアドレスを持つデータを削除してください。']);
        }

        $request->session()->regenerateToken();

        // insert用入力値
        $insert = session('insertAdmin');

        // 設定用トークンを発行（有効期限1時間）
        $token = Utils::makeRandomStr();
        $url = URL::temporarySignedRoute('officeSetPwInput', Carbon::now()->addHours(1), ['token' => $token]);
        $insert['remember_token'] = $token;

        try {
            DB::beginTransaction();

            // 登録
            $newId = DB::table('admins')->insertGetId($insert);
            $admin = DB::table('admins')->where('id', $newId)->first();

            // メール通知
            $subject = 'アカウント発行完了のお知らせ';
            $to = $admin->email;
            $data = ['assign' => ['admin' => $admin, 'url' => $url]];
            Mail::to($to)->queue(new AdminCreateMail($subject, $data));

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '管理者登録（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeAdminCreateInput')->withInput(session('createInputAdmin'))->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '管理者登録（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeAdminCreateInput')->withInput(session('createInputAdmin'))->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeAdminCreateComplete');
    }

    /**
     * 管理者登録（完了）
     *
     * @return View
     */
    public function createComplete(Request $request)
    {
        // フォームで使ったセッションを削除
        session()->forget(['createInputAdmin', 'insertAdmin']);

        $assign = [];

        return view('office/admins/create/complete', compact('assign'));
    }

    /**
     * 管理者編集（入力）
     *
     * @return View
     */
    public function editInput(Request $request, $id)
    {
        // 管理者取得
        $assign['record'] = DB::table('admins')->where('id', $id)->whereNull('deleted_at')->first();
        if (! $assign['record']) {
            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', '管理者が存在しません。');
        }

        // 初期登録の管理者チェック
        if (! $assign['record']->activated_at) {
            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', '管理者がアカウント発行を完了していません。パスワード設定用メールを送っていますので、パスワードを設定するようお伝えください。');
        }

        // 権限
        $assign['roles'] = RoleModel::getBy(['method' => 'pluck']);

        // ログインロック日時
        $assign['record']->login_locked_at_formatted = $assign['record']->login_locked_at
        ? Carbon::parse($assign['record']->login_locked_at)->addHour()->format('Y年m月d日 H時i分')
        : null;

        return view('office/admins/edit/input', compact('assign'));
    }

    /**
     * 管理者編集（確認）
     *
     * @param  Request  $request
     * @return View
     */
    public function editConfirm(EditRequest $request, $id)
    {
        $input = $request->validated();

        // 管理者取得
        $assign['record'] = DB::table('admins')->where('id', $id)->whereNull('deleted_at')->first();
        if (! $assign['record']) {
            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', '管理者が存在しません。');
        }

        // 初期登録の管理者チェック
        if (! $assign['record']->activated_at) {
            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', '管理者がアカウント発行を完了していません。パスワード設定用メールを送っていますので、パスワードを設定するようお伝えください。');
        }

        // メールアドレスユニークチェック
        $exists = DB::table('admins')
            ->where('id', '!=', $id)
            ->where('email', $request->email)
            ->whereNull('deleted_at')
            ->exists();
        if ($exists) {
            return redirect()->route('officeAdminEditInput', ['id' => $id])->withInput($input)->withErrors(['email' => '既に同じメールアドレスが登録済みです。同じメールアドレスを登録したい場合は、そのメールアドレスを持つデータを削除してください。']);
        }

        // 退職日を入力した場合、編集対象以外に登録されている有効な管理者レコード（activated_at IS NOT NULL AND terminated_at IS NULL AND deleted_at IS NULL）が存在しない場合
        if ($input['terminated_at']) {
            $exists = DB::table('admins')->where('id', '<>', $id)->whereNotNull('activated_at')->whereNull('terminated_at')->whereNull('deleted_at')->exists();
            if (! $exists) {
                return redirect()->route('officeAdminEditInput', ['id' => $id])->withInput($input)->withErrors(['terminated_at' => 'ログイン可能な管理者が存在しないため、退職させられません。']);
            }
        }

        // 権限
        $assign['roles'] = RoleModel::getBy(['method' => 'pluck']);

        // 確認ページ表示用に加工、登録用に加工
        $update = [];
        foreach ($input as $key => $value) {
            switch ($key) {
                case 'password':
                    $assign['confirm'][$key] = '****';
                    $update['password'] = Hash::make($value);
                    // パスワードが入力されていない場合、変更しない
                    if (! $value) {
                        $assign['confirm'][$key] = '';
                        unset($update['password']);
                    }
                    break;

                case 'role_id':
                    $assign['confirm'][$key] = $assign['roles'][$value] ?? '';
                    $update[$key] = $value;
                    break;

                case 'terminated_at':
                    $assign['confirm'][$key] = Utils::dateToYmdJa($value);
                    $update[$key] = $value ? date('Y-m-d H:i:s', strtotime("{$value} ".date('H:i:s'))) : null;
                    break;

                case 'login_locked_at':
                    $assign['confirm'][$key] = 0;
                    if ($value) {
                        $assign['confirm'][$key] = 1;
                        $update[$key] = null;
                    }
                    break;

                default:
                    $assign['confirm'][$key] = $value;
                    $update[$key] = $value;
                    break;
            }
        }

        session(['updateInputAdmin' => $input, 'updateAdmin' => $update]);

        return view('office/admins/edit/confirm', compact('assign'));
    }

    /**
     * 管理者編集（処理）
     *
     * @return RedirectResponse
     */
    public static function editExecute(Request $request, $id)
    {
        // 書き直し処理
        if ($request->back) {
            return redirect()->route('officeAdminEditInput', ['id' => $id])->withInput(session('updateInputAdmin'));
        }

        // 管理者取得
        $record = DB::table('admins')->where('id', $id)->whereNull('deleted_at')->first();
        if (! $record) {
            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', '管理者が存在しません。');
        }

        // 初期登録の管理者チェック
        if (! $record->activated_at) {
            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', '管理者がアカウント発行を完了していません。パスワード設定用メールを送っていますので、パスワードを設定するようお伝えください。');
        }

        // メールアドレスユニークチェック
        $exists = DB::table('admins')
            ->where('id', '!=', $id)
            ->where('email', session('updateAdmin.email'))
            ->whereNull('deleted_at')
            ->exists();
        if ($exists) {
            return redirect()->route('officeAdminEditInput', ['id' => $id])->withInput(session('updateInputAdmin'))->withErrors(['email' => '既に同じメールアドレスが登録済みです。同じメールアドレスを登録したい場合は、そのメールアドレスを持つデータを削除してください。']);
        }

        // 退職日を入力した場合、編集対象以外に登録されている有効な管理者レコード（activated_at IS NOT NULL AND terminated_at IS NULL AND deleted_at IS NULL）が存在しない場合
        if (session('updateAdmin.terminated_at')) {
            $exists = DB::table('admins')->where('id', '<>', $id)->whereNotNull('activated_at')->whereNull('terminated_at')->whereNull('deleted_at')->exists();
            if (! $exists) {
                return redirect()->route('officeAdminEditInput', ['id' => $id])->withInput(session('updateInputAdmin'))->withErrors(['terminated_at' => 'ログイン可能な管理者が存在しないため、退職させられません。']);
            }
        }

        $request->session()->regenerateToken();

        // update用入力値
        $update = session('updateAdmin');

        try {
            DB::beginTransaction();

            // 更新
            DB::table('admins')->where('id', $id)->whereNull('deleted_at')->update($update);

            // 履歴テーブルへの登録
            DB::table('admins')->where('id', $id)->first();

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '管理者編集（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeAdminEditInput', ['id' => $id])->withInput(session('updateInputAdmin'))->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '管理者編集（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeAdminEditInput', ['id' => $id])->withInput(session('updateInputAdmin'))->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeAdminEditComplete', ['id' => $id]);
    }

    /**
     * 管理者編集（完了）
     *
     * @return View
     */
    public function editComplete(Request $request, $id)
    {
        // フォームで使ったセッションを削除
        session()->forget(['updateInputAdmin', 'updateAdmin']);

        $assign['id'] = $id;

        return view('office/admins/edit/complete', compact('assign'));
    }

    /**
     * 管理者パスワード再通知（処理）
     *
     * @return RedirectResponse
     */
    public function remindExecute(Request $request, $id)
    {
        $request->session()->regenerateToken();

        // 管理者取得
        $admin = DB::table('admins')->where('id', $id)->whereNull('deleted_at')->first();
        if (! $admin) {
            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', '管理者が存在しません。');
        }

        // 設定用トークンを発行（有効期限1時間）
        $token = Utils::makeRandomStr();
        $url = URL::temporarySignedRoute('officeSetPwInput', Carbon::now()->addHours(1), ['token' => $token]);

        try {
            DB::beginTransaction();

            // 更新
            DB::table('admins')->where('id', $id)->update(['remember_token' => $token]);

            // メール通知
            $subject = 'アカウント発行完了のお知らせ';
            $to = $admin->email;
            $data = ['assign' => ['admin' => $admin, 'url' => $url]];
            if (config('app.env') === 'local') {
                Mail::to($to)->queue(new AdminCreateMail($subject, $data));
            } else {
                $messageId = EmailService::queueEmail([
                    'from' => config('mail.from.address'),
                    'to' => $to,
                    'subject' => $subject,
                    'body' => view('office/admins/create/notice', $data)->render(),
                ]);

                Utils::log('info', 'MAIL QUEUE 管理者パスワード再通知（処理） '.__METHOD__.'#'.__LINE__." >>> messageId: {$messageId}");
            }

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '管理者パスワード再通知（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '管理者パスワード再通知（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('success', 'パスワード設定メールを再送信しました。');
    }

    /**
     * 管理者削除（処理）
     *
     * @return RedirectResponse
     */
    public function deleteExecute(Request $request, $id)
    {
        $request->session()->regenerateToken();

        // 削除対象以外に登録されている有効な管理者レコード（activated_at IS NOT NULL AND terminated_at IS NULL AND deleted_at IS NULL）が存在しない場合
        $exists = DB::table('admins')->where('id', '<>', $id)->whereNotNull('activated_at')->whereNull('terminated_at')->whereNull('deleted_at')->exists();
        if (! $exists) {
            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', 'ログイン可能な管理者が存在しなくなるため削除できません。');
        }

        try {
            DB::beginTransaction();

            // 削除
            DB::table('admins')->where('id', $id)->update(['deleted_at' => Carbon::now()]);

            // 履歴テーブルへの登録
            DB::table('admins')->where('id', $id)->first();

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '管理者削除（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '管理者削除（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        // ログイン中の管理者を削除した場合はログアウト
        if ($id === Auth::id()) {
            return redirect()->route('officeLogout');
        }

        return redirect()->route('officeAdminIndex', session('officeAdminIndexSearchParams'))->with('success', '削除しました。');
    }
}
