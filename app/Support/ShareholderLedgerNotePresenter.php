<?php

namespace App\Support;

use App\Models\ShareholderLedgerEntry;

/**
 * يعرض ملاحظات دفتر الجاري بشكل بسيط للمستخدم.
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
            return [
                'title' => '—',
                'badge' => null,
                'badge_class' => 'text-bg-light border',
                'lines' => [],
                'fallback' => null,
            ];
        }

        $isDebit = $entry && $entry->direction === ShareholderLedgerEntry::DIRECTION_DEBIT;
        $amount = $entry ? number_format((float) $entry->amount, 2).' ج.م' : null;
        $here = $entry?->project?->name ?? $entry?->landParcel?->name;

        $from = self::matchOne('/من\s*«([^»]+)»/u', $raw);
        $to = self::matchOne('/إلى\s*«([^»]+)»/u', $raw);
        $pct = null;

        if (preg_match('/توزيع\s+([\d.]+)%/u', $raw, $m)) {
            $pct = (rtrim(rtrim($m[1], '0'), '.') ?: $m[1]).'٪';
        }

        $isTransferNote = ($from !== null || $to !== null)
            && (str_contains($raw, 'توزيع') || str_contains($raw, 'تحويل') || str_contains($raw, 'استلام'));

        if ($isTransferNote) {
            if ($isDebit) {
                return [
                    'title' => 'فلوس خرجت لمشروع تاني',
                    'badge' => 'خرجت',
                    'badge_class' => 'text-bg-danger',
                    'lines' => self::lines([
                        'من' => $here ?? $from,
                        'إلى' => $to,
                        'المبلغ' => $amount,
                        'النسبة' => $pct,
                    ]),
                    'fallback' => null,
                ];
            }

            return [
                'title' => 'فلوس دخلت من مشروع تاني',
                'badge' => 'دخلت',
                'badge_class' => 'text-bg-success',
                'lines' => self::lines([
                    'من' => $from,
                    'إلى' => $here ?? $to,
                    'المبلغ' => $amount,
                    'النسبة' => $pct,
                ]),
                'fallback' => null,
            ];
        }

        if (str_contains($raw, 'مقدم بيعة')) {
            $saleNo = self::matchOne('/بيعة\s*#(\d+)/u', $raw)
                ?? ($entry?->sale_id ? (string) $entry->sale_id : null);

            return [
                'title' => 'مقدم بيعة على حسابك',
                'badge' => 'مقدم',
                'badge_class' => 'text-bg-primary',
                'lines' => self::lines([
                    'المشروع' => $here,
                    'رقم البيعة' => $saleNo,
                    'المبلغ' => $amount,
                ]),
                'fallback' => null,
            ];
        }

        if (str_contains($raw, 'تحصيل') && str_contains($raw, 'دخل حساب')) {
            $cat = self::matchOne('/تحصيل(?:\s+مشروع\s*#\d+)?\s*—\s*(.+?)\s*—\s*دخل/u', $raw);
            $receipt = self::matchOne('/إيصال\s*#(\d+)/u', $raw);

            return [
                'title' => 'تحصيل على حسابك',
                'badge' => 'تحصيل',
                'badge_class' => 'text-bg-info',
                'lines' => self::lines([
                    'المشروع' => $here,
                    'النوع' => $cat,
                    'الإيصال' => $receipt,
                    'المبلغ' => $amount,
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
                'fallback' => self::shorten(str_replace(['تحويل صناديق — ', 'تحويل صناديق'], '', $raw), 100),
            ];
        }

        return [
            'title' => 'ملاحظة',
            'badge' => null,
            'badge_class' => 'text-bg-light border',
            'lines' => [],
            'fallback' => $raw,
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
