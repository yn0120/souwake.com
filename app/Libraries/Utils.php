<?php

namespace App\Libraries;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Utils
{
    /**
     * 指定された範囲の日付配列を返す
     *
     * @param  string  $dateTimeFrom
     * @param  string  $dateTimeTo
     * @param  string  $add  : addSecond, addMinute, addHour, addDay, addWeek, addMonth, addQuarter, addYear
     * @param  string  $returnKey  : 返却する配列のkey
     * @param  string  $returnValue  : 返却する配列のvalue
     * @return array
     */
    public static function dateTimeArray($dateTimeFrom = '2000-01-01', $dateTimeTo = '2000/12/31', $add = 'addDay', $returnKey = 'Y-m-d', $returnValue = 'Y年m月d日')
    {
        // 日時文字列をパース
        try {
            $dateTimeFrom = Carbon::parse($dateTimeFrom);
            $dateTimeTo = Carbon::parse($dateTimeTo);
        } catch (\Throwable $e) {
            // parseエラーの場合は初期配列を返す
            return [date($returnKey) => date($returnValue)];
        }

        // 開始日時が終了日時より未来の場合は初期配列を返す
        if ($dateTimeFrom->gt($dateTimeTo)) {
            return [date($returnKey) => date($returnValue)];
        }

        $dates = [];
        $loopCurrent = $dateTimeFrom->copy();

        // 開始日時から終了日時までループ
        while ($loopCurrent->lte($dateTimeTo)) {
            $dates += [$loopCurrent->format($returnKey) => $loopCurrent->format($returnValue)];
            $loopCurrent->{$add}();
        }

        return $dates;
    }

    /**
     * IPアドレスを返す
     *
     * @param  string  $string
     * @return array
     */
    public static function ipAddresses($string)
    {
        preg_match_all('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $string, $matches);

        return $matches[0] ?? [];
    }

    /**
     * 表示件数の選択肢を返す
     *
     * @param  int  $perPage
     * @param  int  $default
     * @return int
     */
    public static function perPage($perPage, $default = 50)
    {
        $perPage = intval($perPage);

        return ($perPage >= 20 && $perPage <= 99999) ? $perPage : $default;
    }

    /**
     * $min以上$max以下のランダム文字列を返す
     *
     * @param  int  $min
     * @param  int  $max
     */
    public static function makeRandomStr($min = 10, $max = 100): string
    {
        $length = mt_rand($min, $max);
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomStr = '';
        for ($i = 0; $i < $length; $i++) {
            $randomStr .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomStr;
    }

    /**
     * $min以上$max以下のランダム数字を返す
     *
     * @param  int  $min
     * @param  int  $max
     */
    public static function makeRandomNumber($min = 10, $max = 100): string
    {
        $length = mt_rand($min, $max);
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomStr = '';
        for ($i = 0; $i < $length; $i++) {
            $randomStr .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomStr;
    }

    /**
     * 指定されたURLのパスを返す
     */
    public static function urlToPath($url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        return $query ? "{$path}?{$query}" : $path ?? '';
    }

    /**
     * 指定された範囲の数字配列を返す
     */
    public static function rangeNumber($start, $end): array
    {
        $data = [];
        for ($i = $start; $i <= $end; $i++) {
            $data[$i] = $i;
        }

        return $data;
    }

    /**
     * 指定された範囲の数字配列を単位とともに返す
     */
    public static function rangeNumberWithUnit($start, $end, $unit): array
    {
        $data = [];
        for ($i = $start; $i <= $end; $i++) {
            $data[$i] = "{$i}{$unit}";
        }

        return $data;
    }

    /**
     * 指定された数字の序数を返す
     *
     * @return string
     */
    public static function ordinal($number)
    {
        $number = intval($number);
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return "{$number}th";
        }

        return $number.$ends[$number % 10];
    }

    /**
     * date('Y-m-d')にフォーマットした値を返す
     * デリミターがあればハイフンの代わりに使用する
     */
    public static function dateToYmd($date, $delimiter = '-'): ?string
    {
        return $date ? date("Y{$delimiter}m{$delimiter}d", strtotime($date)) : null;
    }

    /**
     * date('Y年m月d日')にフォーマットした値を返す
     */
    public static function dateToYmdJa($date): ?string
    {
        return $date ? date('Y年m月d日', strtotime($date)) : null;
    }

    /**
     * date('Y年n月j日')にフォーマットした値を返す
     */
    public static function dateToYnjJa($date): ?string
    {
        return $date ? date('Y年n月j日', strtotime($date)) : null;
    }

    /**
     * date('Y-m-d H:i:s')にフォーマットした値を返す
     * デリミターがあれば使用する
     */
    public static function dateToYmdHis($date, $delimiter1 = '-', $delimiter2 = ':'): ?string
    {
        return $date ? date("Y{$delimiter1}m{$delimiter1}d H{$delimiter2}i{$delimiter2}s", strtotime($date)) : null;
    }

    /**
     * date('Y年n月j日 G時i分s秒')にフォーマットした値を返す
     */
    public static function dateToYnjGisJa($date): ?string
    {
        return $date ? date('Y年n月j日 G時i分s秒', strtotime($date)) : null;
    }

    /**
     * 指定した日付が正しければフォーマットに合わせて返す
     *
     * @param  string  $date
     * @param  string  $format
     * @return string|null
     */
    public static function dateFormatYmd($date, $format = 'Y-m-d')
    {
        try {
            $date = Carbon::parse($date)->format('Y-m-d');

            $errors = Carbon::getLastErrors();
            if ($errors['warning_count'] > 0 || $errors['error_count'] > 0) {
                return null;
            }

            return $date;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 指定した日付が正しければフォーマットに合わせて返す
     *
     * @param  string  $date
     * @param  string  $format
     * @return string|null
     */
    public static function dateFormatYmdHis($date, $format = 'Y-m-d')
    {
        try {
            $date = Carbon::parse($date)->format($format);

            $errors = Carbon::getLastErrors();
            if ($errors['warning_count'] > 0 || $errors['error_count'] > 0) {
                return null;
            }

            return $date;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * 指定した日付の翌年の前日をdate('Y-m-d')にフォーマットした値を返す
     */
    public static function nextYearPreviousDate($date): ?string
    {
        if (! $date) {
            $date = date('Y-m-d');
        }

        // 不正な日付フォーマットの場合は処理日を使う
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d', strtotime($date));
        }

        [$year, $month, $day] = explode('-', $date);

        // 不正な日付の場合は処理日を使う
        if (! checkdate($month, $day, $year)) {
            $date = date('Y-m-d', strtotime($date));
        }

        $dateTime = new \DateTime($date);

        return $dateTime->modify('+1 year')->modify('-1 day')->format('Y-m-d');
    }

    /**
     * スペースなどすべて削除
     */
    public static function allTrim($string, $replace = ''): string
    {
        $space = [
            "\x00", "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x20", // ASCII制御文字とスペース
            "\xC2\xA0", "\xE1\x9A\x80", "\xE2\x80\x80", "\xE2\x80\x81", "\xE2\x80\x82", "\xE2\x80\x83", "\xE2\x80\x84", "\xE2\x80\x85", "\xE2\x80\x86", "\xE2\x80\x87", "\xE2\x80\x88", "\xE2\x80\x89", "\xE2\x80\x8A", "\xE2\x80\x8B", "\xE3\x80\x80", // 全角スペースなど
        ];

        // 機種依存文字
        $windowsChars = [
            "\xE2\x85\xA0", "\xE2\x85\xA1", "\xE2\x85\xA2", "\xE2\x85\xA3", "\xE2\x85\xA4", "\xE2\x85\xA5", "\xE2\x85\xA6", "\xE2\x85\xA7", "\xE2\x85\xA8", "\xE2\x85\xA9", // 丸付き数字など
        ];

        $chars = array_merge($space, $windowsChars);
        $pattern = '/(?:'.implode('|', array_map('preg_quote', $chars)).')+/u';

        return preg_replace($pattern, $replace, $string);
    }

    /**
     * 対象文字もしくはハイフンを削除して返す
     */
    public static function trimBy($string, $by = '-'): string
    {
        return str_replace($by, '', $string);
    }

    /**
     * 郵便番号をハイフン区切りにして返す
     */
    public static function zipWithHyphen($zipWithoutHypen): string
    {
        $zip1 = substr($zipWithoutHypen, 0, 3);
        $zip2 = substr($zipWithoutHypen, 3);

        return "{$zip1}-{$zip2}";
    }

    /**
     * アップロード可能なファイルか拡張子チェック
     *
     * @param  string  $extension
     */
    public static function isAllowedExtension($extension): bool
    {
        return in_array(strtolower($extension), ['csv', 'gif', 'html', 'jpeg', 'jpg', 'pdf', 'png', 'webp']);
    }

    /**
     * アップロード可能なファイルかサイズチェック
     */
    public static function uploadSize($size): bool
    {
        return ($size >= 2048000) ? false : true;
    }

    /**
     * S3から取得したファイルパスを返す
     *
     * @return array
     */
    public static function getS3FilePath($uri)
    {
        return Storage::disk('s3')->url($uri);
    }

    /**
     * S3から取得したファイルを返す
     *
     * @return array
     */
    public static function getS3File($uri)
    {
        return Storage::disk('s3')->get($uri);
    }

    /**
     * S3のファイルを削除する
     *
     * @return array
     */
    public static function deleteS3File($uri)
    {
        return Storage::disk('s3')->delete($uri);
    }

    /**
     * メール送信する
     * アカウント登録していないメールアドレスでも送信可能
     *
     * @param  array  $info  メール情報
     * @param  array|null  $rep  置換情報
     *
     * @throws Exception
     */
    public static function sendmail(array $info, ?array $rep = null): bool
    {
        // 必須パラメータのバリデーション
        $requiredParams = ['to', 'subject', 'body', 'fromName', 'fromMail'];
        foreach ($requiredParams as $param) {
            if (! isset($info[$param]) || empty($info[$param])) {
                throw new \Exception("Missing required parameter: {$param}");
            }
        }

        // メールアドレスのバリデーション
        if (! filter_var($info['to'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Invalid email address: {$info['to']}");
        }

        // メール本文の取得と置換
        $body = file_get_contents(resource_path()."/views/emails/{$info['body']}.txt");
        if (is_array($rep) && ! empty($rep)) {
            $body = str_replace(array_keys($rep), array_values($rep), $body);
        }

        // 日本語対応のためのエンコーディング
        $subject = mb_encode_mimeheader($info['subject'], 'UTF-8', 'B');
        $body = chunk_split(base64_encode($body));

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'From: '.mb_encode_mimeheader($info['fromName'], 'UTF-8', 'B')." <{$info['fromMail']}>",
            'Reply-To: '.$info['fromMail'],
        ];

        if (! empty($info['cc'])) {
            $headers[] = 'Cc: '.$info['cc'];
        }

        if (! empty($info['bcc'])) {
            $headers[] = 'Bcc: '.$info['bcc'];
        }

        // メール送信
        $result = mb_send_mail($info['to'], $subject, $body, implode("\r\n", $headers));

        if (! $result) {
            throw new \Exception('Failed to send email: '.error_get_last()['message']);
        }

        return $result;
    }

    /**
     * CURLを実行する
     *
     * @param  array  $params  ['path' => '', 'method' => 'POST', 'input' => []]
     * @return array ['status' => 200, 'data' => []] or ['status' => 500, 'error' => '', 'data' => []]
     */
    public static function curl($params): array
    {
        $curl = curl_init();

        $url = 'https://'.config('app.env_domain').$params['path'];
        $method = $params['method'] ?? 'POST';

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => config('app.env') === 'production', // 本番環境でのみSSL検証を有効に
        ];

        if (config('app.env') !== 'production') {
            $options[CURLOPT_SSL_VERIFYHOST] = 0; // 開発環境ではホスト名の検証も無効に
        }

        if ($method === 'POST') {
            $options[CURLOPT_POSTFIELDS] = $params['input'];
        } elseif ($method === 'GET' && ! empty($params['input'])) {
            $url .= '?'.http_build_query($params['input']);
            $options[CURLOPT_URL] = $url;
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return ['status' => 500, 'error' => $error, 'data' => []];
        }

        if (! $response) {
            return ['status' => 500, 'data' => []];
        }

        $decodedResponse = ['status' => 200, 'data' => json_decode($response, true)];

        return $decodedResponse ?: ['status' => 500, 'error' => 'Invalid JSON response', 'data' => []];
    }

    /**
     * 西暦→和暦変換
     * Utils::toWareki('EX年m月d日') : 令和06年09月01日
     * Utils::toWareki('e.x.n.j') : 令.6.9.1
     *
     * @param  string  $format  'K':元号
     *                          'k':元号略称
     *                          'Q':元号(英語表記)
     *                          'q':元号略称(英語表記)
     *                          'X':和暦年(前ゼロ表記)
     *                          'x':和暦年
     * @param  string  $time  変換対象となる日付(西暦)
     * @return string $result 変換後の日付(和暦)
     */
    public static function toWareki($format, $time = 'now')
    {
        $eras = [
            ['jp' => '令和', 'en' => 'Reiwa', 'start' => '2019-05-01'], // 令和(2019年5月1日〜)
            ['jp' => '平成', 'en' => 'Heisei', 'start' => '1989-01-08'], // 平成(1989年1月8日〜)
            ['jp' => '昭和', 'en' => 'Showa', 'start' => '1926-12-25'], // 昭和(1926年12月25日〜)
            ['jp' => '大正', 'en' => 'Taisho', 'start' => '1912-07-30'], // 大正(1912年7月30日〜)
            ['jp' => '明治', 'en' => 'Meiji', 'start' => '1873-01-01'], // 明治(1873年1月1日〜) ※明治5年以前は旧暦を使用していたため、明治6年以降から対応
        ];

        $datetime = new \DateTime($time);
        $year = $datetime->format('Y');

        foreach ($eras as $era) {
            $eraStart = new \DateTime($era['start']);
            if ($datetime >= $eraStart) {
                $eraYear = $year - $eraStart->format('Y') + 1;
                $eraNameJp = $era['jp'];
                $eraNameEn = $era['en'];
                break;
            }
        }

        $result = strtr($format, [
            'E' => $eraNameJp,
            'e' => mb_substr($eraNameJp, 0, 1),
            'Q' => $eraNameEn,
            'q' => substr($eraNameEn, 0, 1),
            'X' => sprintf('%02d', $eraYear),
            'x' => $eraYear,
        ]);

        return $datetime->format($result);
    }

    /**
     * CSVファイルパスからCSV内容を配列で返す
     *
     * @param  null|array  $csv
     * @return array
     */
    public static function makeCsvArr($filePath)
    {
        // ファイルを読み込み
        $content = file_get_contents($filePath);

        // BOMを除去
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // 文字コードを検出して変換
        $encoding = mb_detect_encoding($content, ['UTF-8', 'SJIS-win', 'EUC-JP', 'JIS'], true);
        if (! $encoding) {
            $content = mb_convert_encoding($content, 'UTF-8');
        } elseif ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // 改行コードを統一
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // 一時ファイルに書き込み
        $tempFile = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($tempFile, $content);

        // 処理
        $handle = fopen($tempFile, 'r');
        $csvData = [];
        $handle = fopen($filePath, 'r');
        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $csvData[] = self::csvEncode($data);
        }

        fclose($handle);
        unlink($tempFile);

        return $csvData;
    }

    /**
     * CSVファイル全体をUTF-8に変換して返す
     *
     * @param  null|array  $csv
     * @return array
     */
    public static function csvEncode($csv = null)
    {
        return array_map(function ($line) {
            // 文字コードの判別
            $detectedEncoding = mb_detect_encoding($line, ['UTF-8', 'SJIS-win', 'EUC-JP', 'JIS'], true);

            // 上記の中にない場合、現在のPHP内部エンコーディングから変換（PHPのエンコーディング限界は注意）
            if (! $detectedEncoding) {
                return mb_convert_encoding($line, 'UTF-8');
            }

            // 日本語の文字化けが考えられる場合、UTF-8に変換
            if ($detectedEncoding !== 'UTF-8') {
                return mb_convert_encoding($line, 'UTF-8', $detectedEncoding);
            }

            // 文字化けしていない、またはUTF-8と判断された場合
            return $line;
        }, $csv);
    }

    /**
     * 文字列を日本の電話番号の市外局番ルールにあわせてハイフンありで返す
     *
     * @param  string  $tel
     * @return string
     */
    public static function telFormat($tel)
    {
        if (! $tel) {
            return null;
        }

        // 数字以外の文字を削除
        $tel = preg_replace('/[^0-9]/', '', $tel);

        // 先頭が0でない場合は0を追加
        if (substr($tel, 0, 1) != '0') {
            $tel = "0{$tel}";
        }

        // 携帯電話・IP電話・PHS
        if (in_array(substr($tel, 0, 3), ['090', '080', '070', '050', '020'])) {
            return substr($tel, 0, 3).'-'.substr($tel, 3, 4).'-'.substr($tel, 7);
        }

        // フリーダイヤル
        if (substr($tel, 0, 4) == '0120') {
            return '0120-'.substr($tel, 4, 3).'-'.substr($tel, 7);
        }

        // 東京・大阪（先頭2桁が03または06）（03-xxxx-xxxx、06-xxxx-xxxx）
        if (in_array(substr($tel, 0, 2), ['03', '06'])) {
            return substr($tel, 0, 2).'-'.substr($tel, 2, 4).'-'.substr($tel, 6);
        }

        // 最初の3桁を市外局番と見なす（012-xxx-xxxx）
        return substr($tel, 0, 3).'-'.substr($tel, 3, 3).'-'.substr($tel, 6);
    }

    /**
     * ロギング
     *
     * @param  string  $level
     * @param  string  $content
     * @return void
     */
    public static function log($level = 'info', $content = '')
    {
        $id = '未ログイン者';
        if (Auth::check()) {
            $id = '実行管理者ID:'.Auth::id();
        }

        Log::{$level}("{$id} {$content}");

    }
}
