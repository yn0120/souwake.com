<?php

namespace App\Http\Middleware\Secrets;

use Closure;

/**
 * ファイル機能のレスポンスに、キャッシュ禁止とXSS対策のヘッダーを付与する。
 *
 * E2E暗号化への移行により、このドメイン上のXSSは「全ファイルの漏洩」に直結するようになった。
 * vault秘密鍵とファイル鍵はブラウザのメモリ上に載っており（extractable=falseなので鍵バイト列
 * そのものは持ち出せないが、復号操作は呼べてしまう）、同一オリジンで走るスクリプトは
 * Service Worker経由で平文を読み出せる。したがってCSPは「あれば良い」ではなく必須の防御になる。
 *
 * 注意: これはHTTPキャッシュ/ページ内容の保存を防ぐものであり、
 * ブラウザのアドレスバー履歴（訪問URLの履歴）自体はサーバー側から消すことはできない。
 * 履歴を残したくない場合は、閲覧側でプライベートブラウジングを利用する必要がある。
 */
class NoStoreCache
{
    public function handle($request, Closure $next)
    {
        // インラインの<script>を許可するためのnonce。'unsafe-inline'を使うとCSPが実質無効化
        // されてしまうため、リクエストごとにランダムなnonceを発行してビューへ渡す。
        // ビュー側は <script nonce="{{ $cspNonce }}"> と書く（office/secrets/*.blade.php）。
        $nonce = base64_encode(random_bytes(16));
        view()->share('cspNonce', $nonce);

        $response = $next($request);

        // privateも明示する。no-storeがあれば共有キャッシュには載らないが、
        // 経路上のプロキシ実装差を考慮して二重に宣言しておく。
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');

        // blob: を img-src/media-src に許可しているのは、Service Workerが組み立てた
        // 復号済みレスポンスをPlyrがblob URLとして扱う場合があるため。
        // connect-src 'self' により、万一XSSを踏んでも平文を外部へ送信できない
        // （これがE2Eにおける最後の防波堤になる）。
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'",
            // Sneatテーマが要素のstyle属性を多用しており、'unsafe-inline'を外すと画面が崩れる。
            // style経由の情報漏洩はscriptに比べれば限定的なため、ここは許容する。
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' blob: data:",
            "media-src 'self' blob:",
            "font-src 'self'",
            "connect-src 'self'",
            "worker-src 'self'",
            "object-src 'none'",
            "base-uri 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
        ]));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
