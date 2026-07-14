<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Http\Requests\Office\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * office.souwake.com専用の簡易ログイン。
 *
 * admin.souwake.com（通常のOfficeパネル）とはあえてセッションを共有しない設計のため
 * （SESSION_DOMAINがnullでCookieがホスト単位のため元々共有されないが、意図的にそうしている）、
 * 同じadminsテーブル・パスワードで再ログインが必要になる。
 *
 * Stage1では既存ログイン（app/Http/Controllers/Office/OfficeAuthController）にある
 * ワンタイムキーによるメール2段階認証・ログイン試行制限は簡略化のため実装していない。
 * 必要であればStage2でこの認証フローを揃える想定。
 */
class OfficeSecretsAuthController extends Controller
{
    public function loginInput(Request $request)
    {
        $assign = [];

        return view('office/secrets/auth/login', compact('assign'));
    }

    public function loginExecute(LoginRequest $request)
    {
        $request->session()->regenerateToken();

        $input = $request->validated();

        $admin = DB::table('admins')
            ->where('email', $input['email'])
            ->whereNotNull('activated_at')
            ->whereNull('terminated_at')
            ->whereNull('deleted_at')
            ->first();

        if (! $admin || ! Hash::check($input['password'], $admin->password)) {
            return redirect()->route('officeSecretsLoginInput')->withInput($input)->with('error', 'ログインに失敗しました。メールアドレスとパスワードが正しいかご確認ください。');
        }

        Auth::loginUsingId($admin->id);
        DB::table('admins')->where('id', $admin->id)->update(['last_activity_at' => now()]);

        return redirect()->route('officeSecretsIndex');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->flush();

        return redirect()->route('officeSecretsLoginInput')->with('success', 'ログアウトしました。');
    }
}
