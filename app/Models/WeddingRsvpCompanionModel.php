<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeddingRsvpCompanionModel extends Model
{
    /** 1回答あたりに登録できる同伴者の上限 */
    public const MAX_COUNT = 20;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wedding_rsvp_companions';

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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 紐づく出欠回答
     */
    public function rsvp(): BelongsTo
    {
        return $this->belongsTo(WeddingRsvpModel::class, 'wedding_rsvp_id');
    }

    /**
     * お食事の選択肢
     *
     * @return array<string, string>
     */
    public static function mealOptions(): array
    {
        return [
            'adult' => '大人メニュー',
            'child_lunch' => 'お子様ランチ',
            'child_plate' => 'お子様プレート',
            'none' => '不要',
        ];
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
     * お食事の表示名
     */
    public function mealLabel(): string
    {
        return self::mealOptions()[$this->meal] ?? '未選択';
    }
}
