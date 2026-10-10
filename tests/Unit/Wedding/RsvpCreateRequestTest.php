<?php

namespace Tests\Unit\Wedding;

use App\Http\Requests\Wedding\RsvpCreateRequest;
use App\Models\WeddingRsvpModel;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * weddingフォーム（出欠回答）のバリデーションルールの単体テスト。
 *
 * RsvpCreateRequest::rules()はリクエスト内容（country・attendance・companion_flag）に
 * よって必須/任意が切り替わるため、DBやHTTPを介さずルールだけを検証する。
 */
class RsvpCreateRequestTest extends TestCase
{
    /**
     * ご出席・日本在住・同伴者なしの最小構成（これを土台に各テストで値を書き換える）
     */
    private function validAttendingJpPayload(array $overrides = []): array
    {
        return array_merge([
            'attendance' => 'attending',
            'name_sei' => '山田',
            'name_mei' => '太郎',
            'kana_sei' => 'ヤマダ',
            'kana_mei' => 'タロウ',
            'country' => WeddingRsvpModel::COUNTRY_JP,
            'postal_code' => '9000001',
            'prefecture' => '沖縄県',
            'city' => '那覇市',
            'address' => '1-2-3',
            'phone' => '090-1234-5678',
            'email' => 'taro@example.com',
            'companion_flag' => '0',
        ], $overrides);
    }

    private function validate(array $data): \Illuminate\Contracts\Validation\Validator
    {
        $request = RsvpCreateRequest::create('/rsvp', 'POST', $data);

        return Validator::make($data, $request->rules(), $request->messages(), $request->attributes());
    }

    public function test_出席_日本在住の必須項目が揃っていれば通る(): void
    {
        $validator = $this->validate($this->validAttendingJpPayload());

        $this->assertTrue($validator->passes());
    }

    public function test_姓が未入力だと弾かれる(): void
    {
        $validator = $this->validate($this->validAttendingJpPayload(['name_sei' => '']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name_sei', $validator->errors()->toArray());
    }

    public function test_フリガナがカタカナでないと弾かれる(): void
    {
        $validator = $this->validate($this->validAttendingJpPayload(['kana_sei' => 'やまだ']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('kana_sei', $validator->errors()->toArray());
    }

    public function test_日本の郵便番号が桁数不足だと弾かれる(): void
    {
        $validator = $this->validate($this->validAttendingJpPayload(['postal_code' => '9000']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('postal_code', $validator->errors()->toArray());
    }

    public function test_電話番号の形式が不正だと弾かれる(): void
    {
        $validator = $this->validate($this->validAttendingJpPayload(['phone' => 'abc']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_メールアドレスの形式が不正だと弾かれる(): void
    {
        $validator = $this->validate($this->validAttendingJpPayload(['email' => 'not-an-email']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_欠席の場合は住所と電話番号が未入力でも通る(): void
    {
        $data = $this->validAttendingJpPayload([
            'attendance' => 'absent',
            'country' => null,
            'postal_code' => null,
            'prefecture' => null,
            'city' => null,
            'address' => null,
            'phone' => null,
        ]);

        $validator = $this->validate($data);

        $this->assertTrue($validator->passes());
    }

    public function test_アメリカ在住の場合はフリガナが任意になる(): void
    {
        $data = $this->validAttendingJpPayload([
            'country' => WeddingRsvpModel::COUNTRY_US,
            'kana_sei' => '',
            'kana_mei' => '',
            'postal_code' => '90210',
            'prefecture' => null,
            'state' => 'California',
            'city' => 'Los Angeles',
            'address' => '123 Main St',
            'phone' => '+1 555-123-4567',
        ]);

        $validator = $this->validate($data);

        $this->assertTrue($validator->passes());
    }

    public function test_アメリカ在住の場合は州の選択が必須(): void
    {
        $data = $this->validAttendingJpPayload([
            'country' => WeddingRsvpModel::COUNTRY_US,
            'kana_sei' => '',
            'kana_mei' => '',
            'postal_code' => '90210',
            'prefecture' => null,
            'city' => 'Los Angeles',
            'address' => '123 Main St',
            'phone' => '+1 555-123-4567',
        ]);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('state', $validator->errors()->toArray());
    }

    public function test_アメリカ在住の場合はZIPの形式で検証される(): void
    {
        $data = $this->validAttendingJpPayload([
            'country' => WeddingRsvpModel::COUNTRY_US,
            'kana_sei' => '',
            'kana_mei' => '',
            'postal_code' => '900-0001',
            'prefecture' => null,
            'state' => 'California',
            'city' => 'Los Angeles',
            'address' => '123 Main St',
            'phone' => '+1 555-123-4567',
        ]);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('postal_code', $validator->errors()->toArray());
    }

    public function test_同伴者ありを選んだ場合は同伴者情報が必須(): void
    {
        $data = $this->validAttendingJpPayload(['companion_flag' => '1']);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('companions', $validator->errors()->toArray());
    }

    public function test_同伴者ありで同伴者情報が揃っていれば通る(): void
    {
        $data = $this->validAttendingJpPayload([
            'companion_flag' => '1',
            'companions' => [
                [
                    'name_sei' => '山田',
                    'name_mei' => '花子',
                    'kana_sei' => 'ヤマダ',
                    'kana_mei' => 'ハナコ',
                    'meal' => 'adult',
                ],
            ],
        ]);

        $validator = $this->validate($data);

        $this->assertTrue($validator->passes());
    }

    public function test_欠席の場合は同伴者ありを選んでも同伴者情報は不要(): void
    {
        $data = $this->validAttendingJpPayload([
            'attendance' => 'absent',
            'country' => null,
            'postal_code' => null,
            'prefecture' => null,
            'city' => null,
            'address' => null,
            'phone' => null,
            'companion_flag' => '1',
        ]);

        $validator = $this->validate($data);

        $this->assertTrue($validator->passes());
    }

    public function test_ハニーポットに値が入っていると弾かれる(): void
    {
        $data = $this->validAttendingJpPayload(['contact_note' => 'スパム業者です']);

        $validator = $this->validate($data);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('contact_note', $validator->errors()->toArray());
    }

    public function test_出欠の選択肢が不正だと弾かれる(): void
    {
        $validator = $this->validate($this->validAttendingJpPayload(['attendance' => 'maybe']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('attendance', $validator->errors()->toArray());
    }
}
