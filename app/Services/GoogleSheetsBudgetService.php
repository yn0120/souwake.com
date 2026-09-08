<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
use Google\Service\Sheets\CellData;
use Google\Service\Sheets\CellFormat;
use Google\Service\Sheets\GridRange;
use Google\Service\Sheets\Request as SheetsRequest;
use Google\Service\Sheets\RepeatCellRequest;
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

        $valueRange = new ValueRange();
        $valueRange->setValues([$row]);

        $service->spreadsheets_values->append(
            $spreadsheetId,
            "'{$sheetTitle}'!A:E",
            $valueRange,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    /**
     * 共有の入出金台帳（A:日付, B:区分, C:内容, D:入金額, E:出金額, F:残高, G:利用者, H:備考 の8列）の1件を、
     * $startRow行目以降で最初に空いている行へ書き込む。
     * 残高（F列, $row のインデックス5）は渡された値を無視し、直前行の残高を参照する数式で上書きする
     * （例: 99行目なら =F98+D99-IF(OR(G99=$B$6,G99=$B$7),0,E99)）。
     * また、区分（B列）・利用者（G列）は中央寄せで書き込む。
     *
     * @param  string  $spreadsheetUrl
     * @param  array  $row  A列から順に並べる8要素の配列（F列は数式で上書きされるため何を渡しても良い）
     * @param  string  $credentialsBase64  管理者ごとにアップロードされたサービスアカウントJSON鍵（base64エンコード済み）
     * @param  int  $startRow  データが入り得る最初の行（これより前は基本情報欄のため探索・書き込みしない）
     * @return void
     */
    public static function appendTransferEntry($spreadsheetUrl, array $row, $credentialsBase64, $startRow)
    {
        $spreadsheetId = self::extractSpreadsheetId($spreadsheetUrl);
        if (! $spreadsheetId) {
            throw new \InvalidArgumentException('スプレッドシートURLからIDを取得できませんでした。');
        }

        $service = new Sheets(self::makeClient($credentialsBase64));
        $sheetProperties = $service->spreadsheets->get($spreadsheetId)->getSheets()[0]->getProperties();
        $sheetId = $sheetProperties->getSheetId();
        $sheetTitle = $sheetProperties->getTitle();

        // A列を$startRow行目以降で読み、最初に空いている行を探す
        $existing = $service->spreadsheets_values->get($spreadsheetId, "'{$sheetTitle}'!A{$startRow}:A")->getValues() ?? [];
        $targetRow = $startRow + count($existing);
        $previousRow = $targetRow - 1;

        $row[5] = "=\$F{$previousRow}+\$D{$targetRow}-IF(OR(\$G{$targetRow}=\$B\$6,\$G{$targetRow}=\$B\$7),0,\$E{$targetRow})";

        $valueRange = new ValueRange();
        $valueRange->setValues([$row]);

        $service->spreadsheets_values->update(
            $spreadsheetId,
            "'{$sheetTitle}'!A{$targetRow}:H{$targetRow}",
            $valueRange,
            ['valueInputOption' => 'USER_ENTERED']
        );

        self::centerAlignColumns($service, $spreadsheetId, $sheetId, $targetRow, [1, 6]);
    }

    /**
     * 指定した行の指定列（0始まり）を中央寄せにする
     *
     * @param  Sheets  $service
     * @param  string  $spreadsheetId
     * @param  int  $sheetId
     * @param  int  $row
     * @param  array  $columnIndexes
     * @return void
     */
    private static function centerAlignColumns(Sheets $service, $spreadsheetId, $sheetId, $row, array $columnIndexes)
    {
        $requests = [];
        foreach ($columnIndexes as $columnIndex) {
            $requests[] = new SheetsRequest([
                'repeatCell' => new RepeatCellRequest([
                    'range' => new GridRange([
                        'sheetId' => $sheetId,
                        'startRowIndex' => $row - 1,
                        'endRowIndex' => $row,
                        'startColumnIndex' => $columnIndex,
                        'endColumnIndex' => $columnIndex + 1,
                    ]),
                    'cell' => new CellData([
                        'userEnteredFormat' => new CellFormat(['horizontalAlignment' => 'CENTER']),
                    ]),
                    'fields' => 'userEnteredFormat.horizontalAlignment',
                ]),
            ]);
        }

        $service->spreadsheets->batchUpdate($spreadsheetId, new BatchUpdateSpreadsheetRequest(['requests' => $requests]));
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
