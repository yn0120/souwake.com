<?php

namespace App\Http\Middleware\Secrets;

use Closure;

/**
 * 秘密ファイル機能のレスポンスをブラウザ・中間プロキシにキャッシュさせない。
 *
 * 注意: これはHTTPキャッシュ/ページ内容の保存を防ぐものであり、
 * ブラウザのアドレスバー履歴（訪問URLの履歴）自体はサーバー側から消すことはできない。
 * 履歴を残したくない場合は、閲覧側でプライベートブラウジングを利用する必要がある。
 */
class NoStoreCache
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');

        return $response;
    }
}
