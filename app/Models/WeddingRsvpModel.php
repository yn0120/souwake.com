<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeddingRsvpModel extends Model
{
    /** ご住所の国：日本 */
    public const COUNTRY_JP = 'JP';

    /** ご住所の国：アメリカ */
    public const COUNTRY_US = 'US';

    /** 出欠：出席 */
    public const ATTENDANCE_ATTENDING = 'attending';

    /** 出欠：欠席 */
    public const ATTENDANCE_ABSENT = 'absent';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wedding_rsvps';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'companion_flag' => 'boolean',
            'arrival_date' => 'date',
            'departure_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * 指定された条件でレコードを返す
     *
     * @param  array  $params
     *                         - id: int 指定されたidで絞り込み
     *                         - ids: array 指定されたid配列で絞り込み
     *                         - email: string 指定されたメールアドレスで絞り込み
     *                         - attendance: string|array 指定された出欠で絞り込み
     *                         - withDeleted: bool true=削除済も含む (デフォルトはfalse)
     *                         - method: string 取得方法 'first', 'get', 'count' (デフォルトは'get')
     * @return mixed
     */
    public static function getBy($params = [])
    {
        $builder = self::when(isset($params['id']) && $params['id'], function ($query) use ($params) {
            return $query->where('id', $params['id']);
        })
            ->when(isset($params['ids']) && $params['ids'], function ($query) use ($params) {
                return $query->whereIn('id', $params['ids']);
            })
            ->when(isset($params['email']) && $params['email'], function ($query) use ($params) {
                return $query->where('email', $params['email']);
            })
            ->when(isset($params['attendance']) && $params['attendance'], function ($query) use ($params) {
                return is_array($params['attendance'])
                    ? $query->whereIn('attendance', $params['attendance'])
                    : $query->where('attendance', $params['attendance']);
            })
            ->when(! isset($params['withDeleted']), function ($query) {
                return $query->whereNull('deleted_at');
            })
            ->orderByRaw('id ASC');

        if (isset($params['method']) && $params['method']) {
            return $builder->{$params['method']}();
        }

        return $builder->get();
    }

    /**
     * 出欠の選択肢
     *
     * @return array<string, string>
     */
    public static function attendanceOptions(): array
    {
        return [
            self::ATTENDANCE_ATTENDING => 'ご出席',
            self::ATTENDANCE_ABSENT => 'ご欠席',
        ];
    }

    /**
     * 当日衣装（かりゆしウェア等）のサイズの選択肢
     *
     * @return array<string, string>
     */
    public static function costumeSizeOptions(): array
    {
        return array_combine(
            ['XS', 'S', 'M', 'L', 'LL', '3L'],
            ['XS', 'S', 'M', 'L', 'LL', '3L'],
        );
    }

    /**
     * 出欠の表示名
     */
    public function attendanceLabel(): string
    {
        return self::attendanceOptions()[$this->attendance] ?? '未回答';
    }

    /**
     * ご住所の国の表示名
     */
    public function countryLabel(): string
    {
        return self::countryOptions()[$this->country] ?? (string) $this->country;
    }

    /**
     * ご住所の国の選択肢
     *
     * @return array<string, string>
     */
    public static function countryOptions(): array
    {
        return [
            self::COUNTRY_JP => '日本 / Japan',
            self::COUNTRY_US => 'アメリカ / United States',
        ];
    }

    /**
     * アメリカの州（Zippopotam.usが返すstate名をそのまま値に使う）
     *
     * @return array<string, string>
     */
    public static function usStates(): array
    {
        return [
            'Alabama' => 'Alabama (AL)', 'Alaska' => 'Alaska (AK)', 'Arizona' => 'Arizona (AZ)',
            'Arkansas' => 'Arkansas (AR)', 'California' => 'California (CA)', 'Colorado' => 'Colorado (CO)',
            'Connecticut' => 'Connecticut (CT)', 'Delaware' => 'Delaware (DE)',
            'District of Columbia' => 'District of Columbia (DC)', 'Florida' => 'Florida (FL)',
            'Georgia' => 'Georgia (GA)', 'Hawaii' => 'Hawaii (HI)', 'Idaho' => 'Idaho (ID)',
            'Illinois' => 'Illinois (IL)', 'Indiana' => 'Indiana (IN)', 'Iowa' => 'Iowa (IA)',
            'Kansas' => 'Kansas (KS)', 'Kentucky' => 'Kentucky (KY)', 'Louisiana' => 'Louisiana (LA)',
            'Maine' => 'Maine (ME)', 'Maryland' => 'Maryland (MD)', 'Massachusetts' => 'Massachusetts (MA)',
            'Michigan' => 'Michigan (MI)', 'Minnesota' => 'Minnesota (MN)', 'Mississippi' => 'Mississippi (MS)',
            'Missouri' => 'Missouri (MO)', 'Montana' => 'Montana (MT)', 'Nebraska' => 'Nebraska (NE)',
            'Nevada' => 'Nevada (NV)', 'New Hampshire' => 'New Hampshire (NH)', 'New Jersey' => 'New Jersey (NJ)',
            'New Mexico' => 'New Mexico (NM)', 'New York' => 'New York (NY)',
            'North Carolina' => 'North Carolina (NC)', 'North Dakota' => 'North Dakota (ND)',
            'Ohio' => 'Ohio (OH)', 'Oklahoma' => 'Oklahoma (OK)', 'Oregon' => 'Oregon (OR)',
            'Pennsylvania' => 'Pennsylvania (PA)', 'Rhode Island' => 'Rhode Island (RI)',
            'South Carolina' => 'South Carolina (SC)', 'South Dakota' => 'South Dakota (SD)',
            'Tennessee' => 'Tennessee (TN)', 'Texas' => 'Texas (TX)', 'Utah' => 'Utah (UT)',
            'Vermont' => 'Vermont (VT)', 'Virginia' => 'Virginia (VA)', 'Washington' => 'Washington (WA)',
            'West Virginia' => 'West Virginia (WV)', 'Wisconsin' => 'Wisconsin (WI)', 'Wyoming' => 'Wyoming (WY)',
            // 在外のご家族向け（Zippopotam.usはこれらのZIPも米国として返す）
            'Puerto Rico' => 'Puerto Rico (PR)', 'Guam' => 'Guam (GU)', 'Virgin Islands' => 'Virgin Islands (VI)',
        ];
    }

    /**
     * アメリカ在住かどうか
     */
    public function isUnitedStates(): bool
    {
        return $this->country === self::COUNTRY_US;
    }

    /**
     * メールなどで使うお名前の表記。フリガナ未入力（アメリカ在住の方など）では括弧を付けない
     */
    public function fullName(): string
    {
        $name = trim("{$this->name_sei} {$this->name_mei}");
        $kana = trim("{$this->kana_sei} {$this->kana_mei}");

        return $kana === '' ? $name : "{$name}（{$kana}）";
    }

    /**
     * メールなどで使う住所の1行表記（日本は郵便番号→都道府県の順、アメリカは番地→州→ZIPの順）
     */
    public function fullAddress(): string
    {
        if ($this->isUnitedStates()) {
            $street = trim("{$this->address} {$this->building}");

            return trim("{$street}, {$this->city}, {$this->prefecture} {$this->postal_code}, USA", ' ,');
        }

        // 入力はハイフンなしの7桁で届くため、表示時に郵便番号の形へ整える
        $postalCode = preg_replace('/^(\d{3})(\d{4})$/', '$1-$2', (string) $this->postal_code);

        return trim("〒{$postalCode} {$this->prefecture}{$this->city}{$this->address} {$this->building}");
    }

    /**
     * 添付されたお祝い画像
     */
    public function photos(): HasMany
    {
        return $this->hasMany(WeddingRsvpPhotoModel::class, 'wedding_rsvp_id');
    }

    /**
     * 同伴者（連名。フォーム上の並び順）
     */
    public function companions(): HasMany
    {
        return $this->hasMany(WeddingRsvpCompanionModel::class, 'wedding_rsvp_id')->orderBy('sort_no');
    }
}
