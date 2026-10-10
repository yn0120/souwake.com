<?php

namespace Tests\Browser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverDimension;
use Tests\TestCase;

/**
 * weddingフォームのSelenium E2Eテストの土台。
 *
 * ローカルのdocker-compose（selenium/standalone-chrome）とwebコンテナに対して実際に
 * HTTPでアクセスし、ブラウザ操作を行う。実行方法は tests/Browser/README.md を参照。
 */
abstract class WeddingBrowserTestCase extends TestCase
{
    protected RemoteWebDriver $driver;

    /** テスト対象のwedding RSVPフォームのURL（例: https://local-wedding.souwake.com/） */
    protected string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        // ルーティング定義（routes/web.php）と同じ組み立て方でホスト名を作る。
        // 実際のDNSには存在しないため、Chrome起動時にhost-resolver-rulesでwebコンテナへ向ける。
        $host = config('app.env_domain').'wedding.'.config('app.domain');
        $this->baseUrl = "https://{$host}";

        $seleniumHost = env('SELENIUM_HOST', 'http://selenium:4444/wd/hub');
        $resolveTo = env('E2E_RESOLVE_TO', 'web');

        $options = new ChromeOptions;
        $options->addArguments([
            "--host-resolver-rules=MAP {$host} {$resolveTo}",
            '--ignore-certificate-errors', // webコンテナの証明書は自己署名のため
            '--disable-dev-shm-usage',
            '--window-size=1280,1600',
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $capabilities->setCapability('acceptInsecureCerts', true);

        $this->driver = RemoteWebDriver::create($seleniumHost, $capabilities, 10000, 10000);
        $this->driver->manage()->window()->setSize(new WebDriverDimension(1280, 1600));
    }

    protected function tearDown(): void
    {
        $this->driver->quit();

        parent::tearDown();
    }

    /**
     * ラジオボタンは見た目をTailwindの`peer-checked`で作るため`sr-only`で視覚的に隠しており、
     * 手前の装飾用`<span>`がネイティブクリックを奪う（ElementClickInterceptedException）。
     * またローカルはAPP_DEBUG=trueでlaravel-debugbarの固定フッターが送信ボタンに重なるため、
     * こちらもネイティブクリックが通らない。どちらもJS経由のclick()で迂回する。
     */
    protected function click(RemoteWebElement $element): void
    {
        $this->driver->executeScript('arguments[0].click();', [$element]);
    }
}
