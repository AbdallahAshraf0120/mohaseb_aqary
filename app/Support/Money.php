<?php

namespace App\Support;

/**
 * دقة الأرقام العشرية الموحّدة في النظام (مبالغ ونسب).
 */
final class Money
{
    public const SCALE = 5;

    public static function round(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), self::SCALE);
    }

    public static function format(float|int|string|null $value): string
    {
        return number_format((float) ($value ?? 0), self::SCALE, '.', ',');
    }

    /** خطوة حقول الإدخال HTML */
    public static function step(): string
    {
        return '0.00001';
    }

    /** أصغر مبلغ موجب مقبول */
    public static function minPositive(): float
    {
        return 0.00001;
    }
}
