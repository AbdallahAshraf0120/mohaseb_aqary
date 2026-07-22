<?php

namespace App\Support;

use App\Models\ShareholderLedgerEntry;

/**
 * يعرض ملاحظات دفتر الجاري بجملة واحدة واضحة حسب صف الحركة.
 */
class ShareholderLedgerNotePresenter
{
    /**
     * @return array{
     *     title: string,
     *     badge: string|null,
     *     badge_class: string,
     *     lines: list<array{label: string, value: string}>,
     *     fallback: string|null
     * }
     */
    public static function present(?string $notes, ?ShareholderLedgerEntry $entry = null): array
    {
        $raw = trim((string) ($notes ?? ''));
        if ($raw === '') {
            return self::empty();
        }

        $isDebit = $entry && $entry->direction === ShareholderLedgerEntry::DIRECTION_DEBIT;
        $here = $entry?->project?->name ?? $entry?->landParcel?->name;

        $from = self::matchOne('/من\s*«([^»]+)»/u', $raw);
        $to = self::matchOne('/إلى\s*«([^»]+)»/u', $raw);
        $pct = null;
        if (preg_match('/توزيع\s+([\d.]+)%/u', $raw, $m)) {
            $pct = (rtrim(rtrim($m[1], '0'), '.') ?: $m[1]).'٪';
        }

        $isTransfer = ($from !== null || $to !== null)
            && (str_contains($raw, 'توزيع') || str_contains($raw, 'تحويل') || str_contains($raw, 'استلام'));

        if ($isTransfer) {
            // صف المدين = الفلوس طلعت من الوجهة دي
            if ($isDebit) {
                $other = $to ?: 'مشروع آخر';

                return [
                    'title' => 'اتحوّل من هنا إلى «'.$other.'»',
                    'badge' => 'تحويل',
                    'badge_class' => 'text-bg-danger',
                    'lines' => self::lines([
                        'الوجهة' => $other,
                        'نسبة التوزيع' => $pct,
                    ]),
                    'fallback' => null,
                ];
            }

            // صف الدائن = الفلوس وصلت للوجهة دي
            $other = $from ?: 'مشروع آخر';

            return [
                'title' => 'وصل هنا من «'.$other.'»',
                'badge' => 'تحويل',
                'badge_class' => 'text-bg-success',
                'lines' => self::lines([
                    'المصدر' => $other,
                    'نسبة التوزيع' => $pct,
                ]),
                'fallback' => null,
            ];
        }

        if (str_contains($raw, 'صرف من صندوق') || (str_contains($raw, 'دخل حساب المساهم') && str_contains($raw, 'صرف'))) {
            return [
                'title' => 'صرف من صندوق المشروع ودخل على حسابك',
                'badge' => 'صرف',
                'badge_class' => 'text-bg-success',
                'lines' => [],
                'fallback' => null,
            ];
        }

        if (str_contains($raw, 'مقدم بيعة') || str_contains($raw, 'جزء من مقدم')) {
            $saleNo = self::matchOne('/بيعة\s*#(\d+)/u', $raw)
                ?? ($entry?->sale_id ? (string) $entry->sale_id : null);
            $isPartial = str_contains($raw, 'جزء من مقدم');

            return [
                'title' => $isPartial
                    ? 'جزء من مقدم بيعة دخل على حسابك'
                    : 'مقدم بيعة دخل على حسابك',
                'badge' => 'مقدم',
                'badge_class' => 'text-bg-primary',
                'lines' => self::lines([
                    'رقم البيعة' => $saleNo,
                ]),
                'fallback' => null,
            ];
        }

        if (str_contains($raw, 'تحصيل') && str_contains($raw, 'دخل حساب')) {
            $cat = self::matchOne('/تحصيل(?:\s+مشروع\s*#\d+)?\s*—\s*(.+?)\s*—\s*دخل/u', $raw);
            $receipt = self::matchOne('/إيصال\s*#(\d+)/u', $raw);

            return [
                'title' => 'تحصيل دخل على حسابك',
                'badge' => 'تحصيل',
                'badge_class' => 'text-bg-info',
                'lines' => self::lines([
                    'النوع' => $cat,
                    'رقم الإيصال' => $receipt,
                ]),
                'fallback' => null,
            ];
        }

        if (str_contains($raw, 'تحويل صناديق')) {
            return [
                'title' => 'تحويل بين الصناديق',
                'badge' => 'صندوق',
                'badge_class' => 'text-bg-warning',
                'lines' => [],
                'fallback' => null,
            ];
        }

        return [
            'title' => self::shorten($raw, 80),
            'badge' => null,
            'badge_class' => 'text-bg-light border',
            'lines' => [],
            'fallback' => null,
        ];
    }

    /**
     * @return array{title: string, badge: null, badge_class: string, lines: list<empty>, fallback: null}
     */
    private static function empty(): array
    {
        return [
            'title' => '—',
            'badge' => null,
            'badge_class' => 'text-bg-light border',
            'lines' => [],
            'fallback' => null,
        ];
    }

    /**
     * @param  array<string, string|null>  $map
     * @return list<array{label: string, value: string}>
     */
    private static function lines(array $map): array
    {
        $out = [];
        foreach ($map as $label => $value) {
            $value = trim((string) ($value ?? ''));
            if ($value === '') {
                continue;
            }
            $out[] = ['label' => $label, 'value' => $value];
        }

        return $out;
    }

    private static function matchOne(string $pattern, string $raw): ?string
    {
        if (! preg_match($pattern, $raw, $m)) {
            return null;
        }

        $v = trim((string) ($m[1] ?? ''));

        return $v !== '' ? $v : null;
    }

    private static function shorten(string $text, int $max): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)).'…';
    }
}
