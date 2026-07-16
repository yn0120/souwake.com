<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;

class GoogleSheetsBudgetService
{
    /**
     * 家計簿の1件を、指定されたスプレッドシートの先頭シートに追記する
     *
     * @param  string  $spreadsheetUrl
     * @param  array  $row  A列から順に並べる値の配列
     * @param  string  $credentialsBase64  管理者ごとにアップロードされたサービスアカウントJSON鍵（base64エンコード済み）
     * @return void
     */
    public static function appendEntry($spreadsheetUrl, array $row, $credentialsBase64)
    {
        $spreadsheetId = self::extractSpreadsheetId($spreadsheetUrl);
        if (! $spreadsheetId) {
            throw new \InvalidArgumentException('スプレッドシートURLからIDを取得できませんでした。');
        }

        $service = new Sheets(self::makeClient($credentialsBase64));

        // シート名が不明でも書き込めるよう、先頭シートのタイトルを取得する
        $sheetTitle = $service->spreadsheets->get($spreadsheetId)->getSheets()[0]->getProperties()->getTitle();

        $service->spreadsheets_values->append(
            $spreadsheetId,
            "'{$sheetTitle}'!A:E",
            new ValueRange(['values' => [$row]]),
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    /**
     * サービスアカウント認証済みのGoogle Clientを生成する
     *
     * @param  string  $credentialsBase64
     * @return Client
     */
    private static function makeClient($credentialsBase64)
    {
        if (! $credentialsBase64) {
            throw new \RuntimeException('Googleサービスアカウントが設定されていません。');
        }

        $credentials = json_decode(base64_decode($credentialsBase64), true);
        if (! $credentials) {
            throw new \RuntimeException('Googleサービスアカウントの設定値が不正です。');
        }

        $client = new Client();
        $client->setAuthConfig($credentials);
        $client->addScope(Sheets::SPREADSHEETS);

        return $client;
    }

    /**
     * スプレッドシートURLからスプレッドシートIDを抽出する
     *
     * @param  string  $url
     * @return string|null
     */
    public static function extractSpreadsheetId($url)
    {
        if (preg_match('#/spreadsheets/d/([a-zA-Z0-9_-]+)#', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
