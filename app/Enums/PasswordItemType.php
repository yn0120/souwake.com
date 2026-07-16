<?php

namespace App\Enums;

// パスワード管理の項目タイプ
enum PasswordItemType: string
{
    case TEXT = 'text';
    case EMAIL = 'email';
    case PASSWORD = 'password';
    case TEL = 'tel';
    case TEXTAREA = 'textarea';

    public function getLabel(): string
    {
        return match($this) {
            self::TEXT => 'テキスト',
            self::EMAIL => 'メールアドレス',
            self::PASSWORD => 'パスワード',
            self::TEL => '番号',
            self::TEXTAREA => 'テキストエリア',
        };
    }

    public static function getKeys(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getLabels(): array
    {
        return array_map(fn ($case) => $case->getLabel(), self::cases());
    }

    public static function toArray(): array
    {
        return array_combine(self::getKeys(), self::getLabels());
    }
}
