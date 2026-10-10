<?php

namespace Tests\Browser\Wedding;

use App\Models\WeddingRsvpModel;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Tests\Browser\WeddingBrowserTestCase;

/**
 * weddingフォーム（ご出欠回答）のSelenium E2Eテスト。
 *
 * 実際のローカル環境（web・db・horizonコンテナ）に対してブラウザ操作を行い、
 * フォーム送信 → 完了画面への遷移 → DBへの保存までを通しで確認する。
 * 実行方法は tests/Browser/README.md を参照。
 */
class WeddingRsvpFormTest extends WeddingBrowserTestCase
{
    /** @var string[] このテストで作成したメールアドレス（tearDownで必ず削除する） */
    private array $createdEmails = [];

    protected function tearDown(): void
    {
        foreach ($this->createdEmails as $email) {
            WeddingRsvpModel::where('email', $email)->delete();
        }

        parent::tearDown();
    }

    public function test_出席で必須項目を入力して送信すると完了画面に遷移しDBへ保存される(): void
    {
        $email = 'selenium-e2e-'.uniqid().'@example.com';
        $this->createdEmails[] = $email;

        $driver = $this->driver;
        $driver->get("{$this->baseUrl}/#rsvp");

        // ご出欠は初期状態で「ご出席」が選択済みのため触らない
        $driver->findElement(WebDriverBy::id('name_sei'))->sendKeys('山田');
        $driver->findElement(WebDriverBy::id('name_mei'))->sendKeys('太郎');
        $driver->findElement(WebDriverBy::id('kana_sei'))->sendKeys('ヤマダ');
        $driver->findElement(WebDriverBy::id('kana_mei'))->sendKeys('タロウ');

        // 住所の自動入力（外部API）には頼らず、各欄を直接埋める
        $driver->findElement(WebDriverBy::cssSelector('[data-postal-code]'))->sendKeys('9001303');
        $driver->findElement(WebDriverBy::cssSelector('[data-address-prefecture]'))->sendKeys('沖縄県');
        $driver->findElement(WebDriverBy::cssSelector('[data-address-city]'))->sendKeys('国頭郡宜野座村');
        $driver->findElement(WebDriverBy::cssSelector('[data-address-street]'))->sendKeys('惣慶1182');

        $driver->findElement(WebDriverBy::id('phone'))->sendKeys('090-1234-5678');
        $driver->findElement(WebDriverBy::id('email'))->sendKeys($email);

        // 同伴者は「なし」のまま送信する
        $this->click($driver->findElement(WebDriverBy::cssSelector('[data-rsvp-submit]')));

        $driver->wait(15)->until(
            WebDriverExpectedCondition::urlContains('/rsvp/complete')
        );

        $this->assertStringContainsString('ご回答ありがとうございました', $driver->getPageSource());

        $rsvp = WeddingRsvpModel::where('email', $email)->first();
        $this->assertNotNull($rsvp, 'フォーム送信後にwedding_rsvpsへレコードが作成されていること');
        $this->assertSame('attending', $rsvp->attendance);
        $this->assertSame('山田', $rsvp->name_sei);
        $this->assertFalse((bool) $rsvp->companion_flag);
    }

    public function test_必須項目が未入力のまま送信するとエラーが表示されフォームに留まる(): void
    {
        $driver = $this->driver;
        $driver->get("{$this->baseUrl}/#rsvp");

        $this->click($driver->findElement(WebDriverBy::cssSelector('[data-rsvp-submit]')));

        $driver->wait(15)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector('.border-clay-500\\/50'))
        );

        $this->assertStringContainsString('ご入力内容をご確認ください', $driver->getPageSource());
        $this->assertStringNotContainsString('ご回答ありがとうございました', $driver->getPageSource());
    }

    public function test_ご欠席を選ぶと住所や旅程の入力欄が隠れる(): void
    {
        $driver = $this->driver;
        $driver->get("{$this->baseUrl}/#rsvp");

        $addressField = $driver->findElement(WebDriverBy::id('address-field'));
        $this->assertTrue($addressField->isDisplayed(), '初期状態（ご出席）では住所欄が表示されていること');

        $this->click($driver->findElement(WebDriverBy::cssSelector('input[name="attendance"][value="absent"]')));

        $driver->wait(5)->until(function () use ($addressField) {
            return ! $addressField->isDisplayed();
        });

        $this->assertFalse(
            $driver->findElement(WebDriverBy::id('attending-only-fields'))->isDisplayed(),
            'ご欠席を選ぶと旅程についての入力欄が隠れること'
        );
    }
}
