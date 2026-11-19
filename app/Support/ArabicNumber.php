<?php

namespace App\Support;

/**
 * تفقيط: تحويل مبلغ لكلام عربي («فقط مائة ألف جنيه مصري لا غير»).
 */
class ArabicNumber
{
    private const ONES = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة',
        'عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];

    private const TENS = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];

    private const HUNDREDS = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];

    /** [مفرد, مثنى, جمع (3-10), مفرد منصوب (11+)] */
    private const SCALES = [
        1 => ['ألف', 'ألفان', 'آلاف', 'ألف'],
        2 => ['مليون', 'مليونان', 'ملايين', 'مليون'],
        3 => ['مليار', 'ملياران', 'مليارات', 'مليار'],
    ];

    public static function words(int $n): string
    {
        if ($n === 0) {
            return 'صفر';
        }
        $groups = [];
        $i = 0;
        while ($n > 0) {
            $groups[$i++] = $n % 1000;
            $n = intdiv($n, 1000);
        }
        $parts = [];
        for ($g = count($groups) - 1; $g >= 0; $g--) {
            $v = $groups[$g];
            if ($v === 0) {
                continue;
            }
            if ($g === 0) {
                $parts[] = self::below1000($v);
                continue;
            }
            [$one, $two, $plural, $acc] = self::SCALES[$g];
            if ($v === 1) {
                $parts[] = $one;
            } elseif ($v === 2) {
                $parts[] = $two;
            } elseif ($v >= 3 && $v <= 10) {
                $parts[] = self::below1000($v).' '.$plural;
            } else {
                // مائتان + ألف → «مائتا ألف» (إضافة)
                $w = self::below1000($v);
                if ($v === 200) {
                    $w = 'مائتا';
                }
                $parts[] = $w.' '.$acc;
            }
        }

        return implode(' و', $parts);
    }

    private static function below1000(int $n): string
    {
        $h = intdiv($n, 100);
        $r = $n % 100;
        $parts = [];
        if ($h > 0) {
            $parts[] = self::HUNDREDS[$h];
        }
        if ($r > 0) {
            if ($r < 20) {
                $parts[] = self::ONES[$r];
            } else {
                $o = $r % 10;
                $t = intdiv($r, 10);
                $parts[] = $o > 0 ? self::ONES[$o].' و'.self::TENS[$t] : self::TENS[$t];
            }
        }

        return implode(' و', $parts);
    }

    public static function money(float $amount, string $currency = 'جنيه مصري', string $sub = 'قرش'): string
    {
        $amount = round($amount, 2);
        $whole = (int) floor($amount);
        $fraction = (int) round(($amount - $whole) * 100);
        $text = 'فقط '.self::words($whole).' '.$currency;
        if ($fraction > 0) {
            $text .= ' و'.self::words($fraction).' '.$sub;
        }

        return $text.' لا غير';
    }
}
