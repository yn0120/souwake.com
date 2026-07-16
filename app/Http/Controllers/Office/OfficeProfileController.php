<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Http\Requests\Office\Profile\UpdateRequest;
use App\Libraries\Utils;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class OfficeProfileController extends Controller
{
    /**
     * プロフィール編集フォーム
     *
     * @return View
     */
    public function index(Request $request)
    {
        $admin = DB::table('admins')->where('id', Auth::id())->first(['name', 'email', 'google_service_account_email']);

        $assign['name'] = $admin->name;
        $assign['email'] = $admin->email;
        $assign['serviceAccountEmail'] = $admin->google_service_account_email;

        return view('office/profile/index', compact('assign'));
    }

    /**
     * プロフィール更新（氏名・メールアドレス・パスワード・Googleサービスアカウント）
     *
     * @return JsonResponse
     */
    public function updateExecute(UpdateRequest $request)
    {
        $adminId = Auth::id();
        $input = $request->validated();

        $update = [
            'name' => $input['name'],
            'email' => $input['email'],
        ];

        if (! empty($input['new_password'])) {
            $update['password'] = Hash::make($input['new_password']);
        }

        if ($request->hasFile('service_account_json')) {
            $raw = $request->file('service_account_json')->get();
            $credentials = json_decode($raw, true);
            if (! is_array($credentials) || ($credentials['type'] ?? null) !== 'service_account' || empty($credentials['client_email']) || empty($credentials['private_key'])) {
                return response()->json(['message' => 'サービスアカウントのJSON鍵ファイルとして正しくありません。'], 422);
            }

            $update['google_service_account_json_base64'] = base64_encode($raw);
            $update['google_service_account_email'] = $credentials['client_email'];
        }

        try {
            DB::table('admins')->where('id', $adminId)->update($update);
        } catch (\Throwable $e) {
            Utils::log('error', 'プロフィール更新（処理） '.__METHOD__.'#'.__LINE__." >>> {$e}");

            return response()->json(['message' => '予期せぬエラーが発生しました。時間をおいて再度お試しください。'], 500);
        }

        return response()->json([
            'message' => '保存しました。',
            'serviceAccountEmail' => $update['google_service_account_email'] ?? null,
        ]);
    }
}
