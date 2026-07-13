<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Http\Requests\Office\Auth\ForgotPwRequest;
use App\Http\Requests\Office\Auth\InitRequest;
use App\Http\Requests\Office\Auth\LoginRequest;
use App\Http\Requests\Office\Auth\OnetimeRequest;
use App\Http\Requests\Office\Auth\SetPwRequest;
use App\Libraries\Utils;
use App\Mail\Office\ForgotPwMail;
use App\Mail\Office\OnetimeKeyMail;
use App\Mail\Office\SetPwMail;
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

class OfficeAuthController extends Controller
{
    /**
     * 管理者初期アカウント設定（入力）
     *
     * @return View
     */
    public function initInput(Request $request)
    {
        if (! session('firstAdmin')) {
            return redirect()->route('officeLoginInput');
        }

        $assign = [];

        return view('office/auth/init/input', compact('assign'));
    }

    /**
     * 管理者初期アカウント設定（処理）
     *
     * @return RedirectResponse
     */
    public function initExecute(InitRequest $request)
    {
        $firstAdmin = session('firstAdmin');
        if (! $firstAdmin) {
            return redirect()->route('officeLoginInput');
        }

        $request->session()->regenerateToken();

        // 入力値
        $input = $request->validated();

        try {
            DB::beginTransaction();

            // adminsを更新
            DB::table('admins')->where('id', $firstAdmin['id'])->where('email', $firstAdmin['email'])->update([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'created_at' => Carbon::now(),
                'activated_at' => Carbon::now(),
            ]);

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '管理者初期アカウント設定（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeInitInput')->withInput($input)->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '管理者初期アカウント設定（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeInitInput')->withInput($input)->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeInitComplete');
    }

    /**
     * 管理者初期アカウント設定（完了）
     *
     * @return View
     */
    public function initComplete(Request $request)
    {
        $assign = [];

        $request->session()->flush();

        return view('office/auth/init/complete', compact('assign'));
    }

    /**
     * パスワードを忘れたら（入力）
     *
     * @return View
     */
    public function forgotPwInput(Request $request)
    {
        $assign = [];

        return view('office/auth/forgot/pw/input', compact('assign'));
    }

    /**
     * パスワードを忘れたら（処理）
     *
     * @return RedirectResponse
     */
    public function forgotPwExecute(ForgotPwRequest $request)
    {
        $request->session()->regenerateToken();

        $input = $request->validated();

        /*
            新規登録した管理者 : created_at IS NOT NULL
            有効中の管理者 : activated_at IS NOT NULL
            退職済みの管理者 : terminated_at IS NOT NULL
            削除済みの管理者 : deleted_at IS NOT NULL
            この処理ができるのは[新規登録した管理者]、[有効中]の管理者のみ
        */
        // 管理者情報を取得
        $admin = DB::table('admins')
            ->where('email', $input['email'])
            ->whereNull('terminated_at')
            ->whereNull('deleted_at')
            ->first();

        // レコードがなければ何もせず完了画面へ（攻撃者に対しヒントを与えないよう、メールアドレスが間違っているとは伝えない）
        if (! $admin) {
            return redirect()->route('officeForgotPwComplete');
        }

        // 設定用トークンを発行（有効期限72時間）
        $token = Utils::makeRandomStr();
        $url = URL::temporarySignedRoute('officeSetPwInput', Carbon::now()->addHours(72), ['token' => $token]);

        try {
            DB::beginTransaction();

            // adminsを更新
            DB::table('admins')->where('id', $admin->id)->update(['remember_token' => $token]);

            // メール通知
            $subject = 'パスワードの設定依頼受付のお知らせ';
            $to = $admin->email;
            $data = ['assign' => ['admin' => $admin, 'url' => $url]];
            Mail::to($to)->queue(new ForgotPwMail($subject, $data));

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', 'パスワードを忘れたら（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeForgotPwInput')->withInput($input)->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', 'パスワードを忘れたら（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeForgotPwInput')->withInput($input)->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeForgotPwComplete');
    }

    /**
     * パスワードを忘れたら（完了）
     *
     * @return View
     */
    public function forgotPwComplete(Request $request)
    {
        $assign = [];

        return view('office/auth/forgot/pw/complete', compact('assign'));
    }

    /**
     * パスワード設定（入力）
     *
     * @return View
     */
    public function setPwInput(Request $request)
    {
        if (! $request->expires || ! $request->token || ! $request->signature) {
            return redirect()->route('officeForgotPwInput')->with('error', '無効なURLです。');
        }

        if (! $request->hasValidSignature()) {
            return redirect()->route('officeForgotPwInput')->with('error', '期限切れURLです。');
        }

        /*
            新規登録した管理者 : created_at IS NOT NULL
            有効中の管理者 : activated_at IS NOT NULL
            退職済みの管理者 : terminated_at IS NOT NULL
            削除済みの管理者 : deleted_at IS NOT NULL
            この画面に来れるのは[新規登録した管理者]と[有効中]の管理者のみ
        */
        // 管理者情報を取得
        $admin = DB::table('admins')
            ->where('remember_token', $request->token)
            ->whereNull('terminated_at')
            ->whereNull('deleted_at')
            ->first();
        if (! $admin) {
            return redirect()->route('officeForgotPwInput')->with('error', '無効なURLです。');
        }

        session(['expires' => $request->expires, 'token' => $request->token, 'signature' => $request->signature, 'admin' => $admin]);

        $assign = [];

        return view('office/auth/set/pw/input', compact('assign'));
    }

    /**
     * パスワード設定（処理）
     *
     * @return RedirectResponse
     */
    public function setPwExecute(SetPwRequest $request)
    {
        if (! session('expires') || ! session('token') || ! session('signature') || ! session('admin')) {
            return redirect()->route('officeForgotPwInput');
        }

        $request->session()->regenerateToken();

        $input = $request->validated();

        try {
            DB::beginTransaction();

            // adminsを更新
            DB::table('admins')->where('id', session('admin.id'))->where('remember_token', session('token'))->update([
                'password' => Hash::make($input['password']),
                'remember_token' => null,
                'activated_at' => Carbon::now(),
            ]);

            // メール通知
            $subject = 'パスワード変更完了のお知らせ';
            $to = session('admin.email');
            $data = ['assign' => ['admin' => session('admin')]];
            Mail::to($to)->queue(new SetPwMail($subject, $data));

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', 'パスワード設定（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeSetPwInput', ['expires' => session('expires'), 'token' => session('token'), 'signature' => session('signature')])->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', 'パスワード設定（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeSetPwInput', ['expires' => session('expires'), 'token' => session('token'), 'signature' => session('signature')])->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeSetPwComplete');
    }

    /**
     * パスワード設定（完了）
     *
     * @return View
     */
    public function setPwComplete(Request $request)
    {
        $assign = [];

        $request->session()->flush();

        return view('office/auth/set/pw/complete', compact('assign'));
    }

    /**
     * ログイン（入力）
     *
     * @return View
     */
    public function loginInput(Request $request)
    {
        $assign = [];

        return view('office/auth/login/input', compact('assign'));
    }

    /**
     * ログイン（処理）
     *
     * @return RedirectResponse
     */
    public function loginExecute(LoginRequest $request)
    {
        $request->session()->regenerateToken();

        $input = $request->validated();

        // ログイン試行回数チェック
        if (session('blocked') && Carbon::now()->lt(session('blocked'))) {
            return redirect()->route('officeLoginInput')->withInput($input)->withError('ログインが制限されました。時間をおいて再度お試しください。');
        }

        /*
            新規登録した管理者 : created_at IS NOT NULL
            有効中の管理者 : activated_at IS NOT NULL
            退職済みの管理者 : terminated_at IS NOT NULL
            削除済みの管理者 : deleted_at IS NOT NULL
            ログインできるのは[有効中]の管理者のみ
        */
        // システムリリース時の初期管理者の場合、IDPWをリセットさせる
        $firstAdmin = DB::table('admins')
            ->where('email', $input['email'])
            ->whereNull('activated_at')
            ->whereNull('terminated_at')
            ->whereNull('deleted_at')
            ->first();
        if ($firstAdmin && Hash::check($input['password'], $firstAdmin->password)) {
            session(['firstAdmin' => $firstAdmin]);

            // 初期アカウント設定ページ
            return redirect()->route('officeInitInput');
        }

        // 通常時
        $admin = DB::table('admins')
            ->where('email', $input['email'])
            ->whereNotNull('activated_at')
            ->whereNull('terminated_at')
            ->whereNull('deleted_at')
            ->first();

        // ログインロックチェック
        if ($admin && $admin->login_locked_at) {
            $lockedAt = Carbon::parse($admin->login_locked_at);

            // ロック時間が1時間を超えている場合、ロック解除
            if (Carbon::now()->gte($lockedAt->addHour())) {
                DB::table('admins')->where('id', $admin->id)->update(['login_locked_at' => null]);
                session(['attempts' => 0]);
            } else {
                // 1時間以内ならロックメッセージを表示
                return redirect()->route('officeLoginInput')->withInput($input)->withError('ご利用のアカウントは、何度もログインに失敗したため一時的にロックされました。しばらく経ってからもう一度ログインしてください。');
            }
        }

        // ログイン成功時
        if ($admin && Hash::check($input['password'], $admin->password)) {
            $onetimeKey = Utils::makeRandomNumber(6, 6); // 6桁固定が良いとのこと

            try {
                DB::beginTransaction();

                // adminsを更新
                DB::table('admins')->where('id', $admin->id)->update(['onetime_key' => $onetimeKey, 'onetime_key_created_at' => Carbon::now(), 'remember_token' => null]);

                // メール通知
                $subject = 'ワンタイムキー発行のお知らせ';
                $to = $admin->email;
                $data = ['assign' => ['admin' => $admin, 'onetimeKey' => $onetimeKey]];
                if (config('app.env') === 'local') {
                    Mail::to($to)->queue(new OnetimeKeyMail($subject, $data));
                } else {
                    $messageId = EmailService::queueEmail([
                        'from' => config('mail.from.address'),
                        'to' => $to,
                        'subject' => $subject,
                        'body' => view('office/auth/login/notice', $data)->render(),
                    ]);

                    Utils::log('info', 'MAIL QUEUE ログイン（処理） '.__METHOD__.'#'.__LINE__." >>> messageId: {$messageId}");
                }

                DB::commit();
            } catch (QueryException $e) {
                DB::rollBack();
                $params = implode(', ', $e->getBindings());
                Utils::log('error', 'ログイン（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

                return redirect()->route('officeLoginInput')->withInput($input)->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
            } catch (\Throwable $e) {
                DB::rollBack();
                Utils::log('error', 'ログイン（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

                return redirect()->route('officeLoginInput')->withInput($input)->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
            }

            session(['admin' => $admin]);

            // ワンタイムキー入力ページ
            return redirect()->route('officeOnetimeInput');
        }

        // ログイン失敗時はログイン試行回数をカウントアップ
        $attempts = session('attempts', 0) + 1;
        session(['attempts' => $attempts]);

        // 5秒間再試行不可
        session(['blocked' => Carbon::now()->addSeconds(5)]);
        // 6回目の入力ミスで処理日時をlogin_locked_atに保存
        if ($attempts >= 6 && $admin) {
            DB::table('admins')->where('id', $admin->id)->update([
                'login_locked_at' => Carbon::now(),
            ]);
        }

        return redirect()->route('officeLoginInput')->withInput($input)->withError('ログインに失敗しました。メールアドレスとパスワードが正しいかご確認ください。');
    }

    /**
     * 管理者ワンタイムキー（入力）
     *
     * @return View
     */
    public function onetimeInput(Request $request)
    {
        $admin = DB::table('admins')
            ->where('email', session('admin.email') ?? 9999)
            ->whereNotNull('activated_at')
            ->whereNull('terminated_at')
            ->whereNull('deleted_at')
            ->first();
        if (! $admin) {
            return redirect()->route('officeLoginInput');
        }

        $assign = [];

        return view('office/auth/login/onetime_key', compact('assign'));
    }

    /**
     * 管理者ワンタイムキー（処理）
     *
     * @return RedirectResponse
     */
    public function onetimeExecute(OnetimeRequest $request)
    {
        if (! session('admin')) {
            return redirect()->route('officeLoginInput')->with('error', 'ログインに失敗しました。同じブラウザでお試しください。');
        }

        $request->session()->regenerateToken();

        $input = $request->validated();

        $admin = DB::table('admins')
            ->where('id', session('admin.id'))
            ->where('onetime_key', $input['onetime_key'])
            ->whereNotNull('activated_at')
            ->whereNull('terminated_at')
            ->whereNull('deleted_at')
            ->first();
        if (! $admin) {
            return redirect()->route('officeLoginInput')->with('error', 'ログインに失敗しました。不正なワンタイムキーです。');
        }

        // 有効期限10分チェック
        if (Carbon::parse($admin->onetime_key_created_at)->addMinutes(10)->isPast()) {
            return redirect()->route('officeLoginInput')->with('error', '期限切れURLです。');
        }

        // 二重ログインを防ぐために一旦ログアウト
        Auth::logout();
        Auth::logout();
        $request->session()->flush();

        try {
            DB::beginTransaction();

            // adminsを更新
            DB::table('admins')->where('id', $admin->id)->update(['onetime_key' => null, 'onetime_key_created_at' => null]);

            // ログイン
            Auth::loginUsingId($admin->id);

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            $params = implode(', ', $e->getBindings());
            Utils::log('error', '管理者ワンタイムキー（処理） '.__METHOD__.'#'.__LINE__."\nSQL: {$e->getSql()}\nParams: {$params}\n{$e}");

            return redirect()->route('officeOnetimeInput')->withInput($input)->with('error', 'データベースエラーが発生しました。時間をおいて再度お試しください。');
        } catch (\Throwable $e) {
            DB::rollBack();
            Utils::log('error', '管理者ワンタイムキー（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return redirect()->route('officeOnetimeInput')->withInput($input)->with('error', '予期せぬエラーが発生しました。時間をおいて再度お試しください。');
        }

        return redirect()->route('officeTop');
    }

    /**
     * ログアウト
     *
     * @return RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        Auth::logout();
        $request->session()->flush();

        return redirect()->route('officeLoginInput')->with('success', 'ログアウトしました。');
    }
}
