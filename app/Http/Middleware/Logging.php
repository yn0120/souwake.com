<?php

namespace App\Http\Middleware;

use App\Libraries\Utils;
use Closure;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\ConditionallyLoadsAttributes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Logging
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // リクエスト開始時間を記録
        $startTime = microtime(true);

        // リクエストIDを生成
        $requestId = uniqid();
        $request->attributes->set('request_id', $requestId);

        // アクセスパスとパラメーター
        $method = $request->method() ?? '';
        $uri = $request->path() === '/' ? '/' : "/{$request->path()}";
        $uri .= $request->query() ? '?'.http_build_query($request->query()) : '';
        $ip = $request->getClientIp() ?? '';
        $userAgent = $request->userAgent() ?? '';
        Utils::log('info', "【{$requestId}】 リクエスト ({$method}) {$uri}, IP: {$ip}, User-Agent: {$userAgent}");

        // コントローラー処理開始前
        $route = $request->route();
        if ($route) {
            $action = $route->getAction();
            if (isset($action['uses']) && is_string($action['uses'])) {
                list($controller, $method) = explode('@', $action['uses']);
                Utils::log('info', "【{$requestId}】 開始: {$controller}@{$method}");

                // リクエストパラメーターをロギング
                $requestParams = [];
                foreach ($request->except('_token') as $key => $value) {
                    switch (true) {
                        case $value instanceof UploadedFile:
                            $fileInfo = [
                                'filename' => $value->getClientOriginalName(),
                                'size' => $value->getSize(),
                                'mime' => $value->getMimeType()
                            ];
                            $requestParams[] = "{$key}: " . json_encode($fileInfo, JSON_UNESCAPED_UNICODE);
                            break;

                        case $value instanceof File:
                            $fileInfo = [
                                'path' => $value->getPathname(),
                                'size' => $value->getSize(),
                            ];
                            $requestParams[] = "{$key}: " . json_encode($fileInfo, JSON_UNESCAPED_UNICODE);
                            break;

                        case $value instanceof ConditionallyLoadsAttributes:
                            $value = "{$key}: [Resource Object]";
                            break;

                        case preg_match('/password/', $key):
                            $value = 'xxxx';
                            break;

                        case $key === '_token':
                            break;

                        case is_array($value):
                            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                            break;

                        case is_null($value):
                            $value = 'null';
                            break;

                        case is_bool($value):
                            $value = $value ? 'true' : 'false';
                            break;

                        case ! is_numeric($value):
                            // 文字列の場合はシングルクォートで囲む
                            $value = "'{$value}'";
                            break;

                        default:
                            break;
                    }

                    $requestParams[] = "{$key}: {$value}";
                }
                try {
                    Utils::log('info', "【{$requestId}】 リクエストパラメーター ".implode(', ', $requestParams));
                } catch (\Throwable $e) {
                    Utils::log('info', "【{$requestId}】 リクエストパラメーター implodeエラー {$e->getMessage()}");
                }

            }
        }

        // SQLをロギング
        DB::listen(function ($query) use ($requestId) {
            // ロギングを除外したいテーブル名
            $excludedTables = ['sessions', 'maintenances', 'insurance_companies', 'routes', 'roles'];
            $excludedPattern = '/(' . implode('|', array_map('preg_quote', $excludedTables)) . ')/i';
            if (! preg_match($excludedPattern, $query->sql)) {
                // バインドパラメータをSQLに埋め込む
                foreach ($query->bindings as $binding) {
                    switch (true) {
                        case is_null($binding):
                            $value = 'NULL';
                            break;

                        case is_bool($binding):
                            $value = $binding ? '1' : '0';
                            break;

                        case is_numeric($binding):
                            $value = strval($binding);
                            // 数値の場合もvarcharの可能性があるのでシングルクォートで囲む
                            $value = "'{$value}'";
                            break;

                        case $binding instanceof Carbon:
                            // Carbon オブジェクトの場合は日時文字列に変換
                            $value = "'{$binding->toDateTimeString()}'";
                            break;

                        default:
                            // 文字列の場合はエスケープ処理を適用
                            $value = "'" . str_replace("'", "''", (string)$binding) . "'";
                            break;
                    }

                    $query->sql = preg_replace('/\?/', $value, $query->sql, 1);
                }

                // 2秒以上かかるクエリはスロークエリとして目印をつけておく
                $isSlowQuery = '';
                if ($query->time > 2000) {
                    $isSlowQuery = '!IS SLOW QUERY! ';
                }
                Utils::log('info', "【{$requestId}】 SQL実行時間: {$query->time}ms, {$isSlowQuery}SQL: {$query->sql}");
            }
        });

        // リクエストの処理を継続
        $response = $next($request);

        // コントローラー処理終了後
        if ($route) {
            $action = $route->getAction();
            if (isset($action['uses']) && is_string($action['uses'])) {
                list($controller, $method) = explode('@', $action['uses']);
                Utils::log('info', "【{$requestId}】 終了: {$controller}@{$method}");
            }
        }

        // レスポンスが返される直前（コントローラー処理終了後）にログを記録
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        $statusCode = $response->getStatusCode() ?? 0;
        Utils::log('info', "【{$requestId}】 レスポンス 実行時間: {$duration}ms, ステータスコード: {$statusCode}, メモリ最大使用量: ".round(memory_get_peak_usage() / 1024 / 1024, 2).'MB');

        return $response;
    }
}
