<?php

namespace App\Support;

/**
 * دقة الأرقام العشرية الموحّدة في النظام (مبالغ ونسب).
 */
final class Money
{
    public const SCALE = 5;

    /**
     * أكبر مبلغ مقبول في حقل واحد (مليار جنيه). بيمنع أخطاء الكتابة زي زيادة أصفار.
     * لو محتاج أكبر من كده غيّر الرقم ده.
     */
    public const MAX_AMOUNT = 1000000000;

    public static function round(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), self::SCALE);
    }

    public static function format(float|int|string|null $value): string
    {
        return number_format((float) ($value ?? 0), self::SCALE, '.', ',');
    }

    /**
     * عرض المبلغ للمستخدم: من غير كسور لو الرقم صحيح (150,000)، وبكسرين بس لو فيه كسور (1,250.50).
     * التخزين والحسابات لسه بدقة SCALE؛ ده للعرض بس.
     */
    public static function display(float|int|string|null $value): string
    {
        $v = round((float) ($value ?? 0), 2);
        if (abs($v - round($v)) < 0.005) {
            return number_format(round($v), 0, '.', ',');
        }

        return number_format($v, 2, '.', ',');
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
