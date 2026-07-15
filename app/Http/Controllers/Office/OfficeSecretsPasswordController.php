<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Libraries\Utils;
use App\Notifications\SecretsPasswordFailedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class OfficeSecretsPasswordController extends Controller
{
    public function input(Request $request)
    {
        $assign = [];

        return view('office/secrets/password', compact('assign'));
    }

    public function verify(Request $request)
    {
        $request->validate(['password' => ['required']]);

        if (Hash::check($request->input('password'), config('secrets.gate_password_hash'))) {
            $request->session()->put('secrets_password_verified', true);

            return redirect()->route('officeSecretsIndex');
        }

        Utils::log('error', 'ファイル機能パスワード認証失敗 OfficeSecretsPasswordController#verify ip='.$request->ip());

        $webhookUrl = config('secrets.slack_alert_webhook_url');
        if ($webhookUrl) {
            try {
                Notification::route('slack', $webhookUrl)
                    ->notify(new SecretsPasswordFailedNotification($request->ip(), $request->userAgent()));
            } catch (\Throwable $e) {
                Utils::log('error', 'Slack通知に失敗 OfficeSecretsPasswordController#verify '.$e->getMessage());
            }
        }

        return redirect('https://souwake.com');
    }
}
