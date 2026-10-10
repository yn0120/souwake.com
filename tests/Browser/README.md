# weddingフォーム Selenium E2Eテスト

`tests/Unit`・`tests/Feature`（`composer test`）とは別枠。実際のローカル環境
（`web`・`db`・`horizon`コンテナ）に対してブラウザから本物のHTTPアクセスを行うため、
以下の外部インフラが必要。CIのデフォルト実行には含めていない。

## 事前準備

1. ローカルのdocker-compose一式が起動していること（`db`・`web`・`horizon`・`app`）。
   `make up`（`docker-compose.local.yml`に`selenium`サービスを追加済み）。
2. `appコンテナ`に`php-webdriver/webdriver`がインストールされていること
   （`composer install`済みなら含まれる）。

## 実行方法

```sh
docker exec app bash -lc "cd /var/www/html/souwake.com && composer test:e2e"
```

`selenium`コンテナ（`selenium/standalone-chrome`）が`app`コンテナと同じDockerネットワーク上で
起動している前提で、`http://selenium:4444/wd/hub`に接続する。接続先を変えたい場合は
環境変数`SELENIUM_HOST`で上書きできる。

テスト対象のURL（例: `https://local-wedding.souwake.com/`）は実在のDNSに存在しないため、
Chrome起動時に`--host-resolver-rules`で`web`コンテナへ直接向けている
（`tests/Browser/WeddingBrowserTestCase.php`）。向き先を変えたい場合は環境変数
`E2E_RESOLVE_TO`で上書きできる。

## 補足

- `selenium`サービスは`docker-compose.local.yml`にのみ追加しており、
  `make ENV=production ...`では使われない（ローカル専用）。
- テストで作成したRSVPレコードは各テストの`tearDown`で削除している。
- `http://localhost:7900`（パスワード: `secret`）でSeleniumの画面を見ながらデバッグできる。
