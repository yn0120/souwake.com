<?php

namespace App\Enums;

// 表示件数
enum PerPage: int
{
    case TWENTY = 20;
    case FIFTY = 50;
    case HUNDRED = 100;
    case TWOHUNDRED = 200;
    case THREEHUNDRED = 300;
    case ALL = 99999;

    public function getLabel(): string
    {
        return match($this) {
            self::TWENTY => 20,
            self::FIFTY => 50,
            self::HUNDRED => 100,
            self::TWOHUNDRED => 200,
            self::THREEHUNDRED => 300,
            self::ALL => 'すべて',
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

    public static function fromLabel(string $label): ?int
    {
        foreach (self::cases() as $case) {
            if ($case->getLabel() === $label) {
                return $case->value;
            }
        }

        return null;
    }
}
