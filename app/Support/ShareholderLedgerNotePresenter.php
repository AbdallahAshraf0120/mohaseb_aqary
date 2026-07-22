<?php

namespace App\Support;

use App\Models\ShareholderLedgerEntry;

/**
 * يعرض ملاحظات دفتر الجاري بلغة أوضح للعميل.
 */
class ShareholderLedgerNotePresenter
{
    /**
     * @return array{title: string, detail: string|null, badge: string|null}
     */
    public static function present(?string $notes, ?ShareholderLedgerEntry $entry = null): array
    {
        $raw = trim((string) ($notes ?? ''));
        if ($raw === '') {
            return [
                'title' => '—',
                'detail' => null,
                'badge' => null,
            ];
        }

        $isDebit = $entry && $entry->direction === ShareholderLedgerEntry::DIRECTION_DEBIT;
        $amountFormatted = null;
        $pct = null;
        $from = null;
        $to = null;
        $movementId = null;

        if (preg_match('/توزيع\s+([\d.]+)%\s*\(([\d.,]+)\s*ج\.?\s*م\.?\)/u', $raw, $m)) {
            $pct = rtrim(rtrim($m[1], '0'), '.') ?: $m[1];
            $amountFormatted = number_format((float) str_replace(',', '', $m[2]), 2);
        }
        if (preg_match('/من حركة\s*#(\d+)/u', $raw, $m)) {
            $movementId = $m[1];
        }
        if (preg_match('/من\s*«([^»]+)»/u', $raw, $m)) {
            $from = trim($m[1]);
        }
        if (preg_match('/إلى\s*«([^»]+)»/u', $raw, $m)) {
            $to = trim($m[1]);
        }

        if ($pct !== null && ($from !== null || $to !== null)) {
            if ($isDebit) {
                $title = $to
                    ? sprintf('تحويل إلى «%s»', $to)
                    : 'تحويل إلى مشروع آخر';
            } else {
                $title = $from
                    ? sprintf('استلام من «%s»', $from)
                    : 'استلام من مشروع آخر';
            }

            $parts = [];
            if ($amountFormatted !== null) {
                $parts[] = $amountFormatted.' ج.م';
            }
            if ($pct !== null) {
                $parts[] = $pct.'٪ من الحركة';
            }
            if ($from && $to) {
                $parts[] = 'من '.$from.' ← '.$to;
            }

            return [
                'title' => $title,
                'detail' => $parts !== [] ? implode('  ·  ', $parts) : null,
                'badge' => 'توزيع',
            ];
        }

        if (preg_match('/مقدم بيعة/u', $raw)) {
            $saleNo = null;
            if (preg_match('/بيعة\s*#(\d+)/u', $raw, $m)) {
                $saleNo = $m[1];
            } elseif ($entry?->sale_id) {
                $saleNo = (string) $entry->sale_id;
            }

            return [
                'title' => 'مقدم بيعة دخل حساب المساهم',
                'detail' => $saleNo ? ('بيعة رقم '.$saleNo) : null,
                'badge' => 'مقدم',
            ];
        }

        if (preg_match('/تحصيل/u', $raw) && preg_match('/دخل حساب المساهم/u', $raw)) {
            $cat = null;
            $receipt = null;
            if (preg_match('/تحصيل(?:\s+مشروع\s*#\d+)?\s*—\s*(.+?)\s*—\s*دخل/u', $raw, $m)) {
                $cat = trim($m[1]);
            }
            if (preg_match('/إيصال\s*#(\d+)/u', $raw, $m)) {
                $receipt = $m[1];
            }

            $detailParts = array_filter([$cat, $receipt ? 'إيصال '.$receipt : null]);

            return [
                'title' => 'تحصيل دخل حساب المساهم',
                'detail' => $detailParts !== [] ? implode('  ·  ', $detailParts) : null,
                'badge' => 'تحصيل',
            ];
        }

        if (str_contains($raw, 'تحويل صناديق')) {
            return [
                'title' => 'تحويل بين الصناديق',
                'detail' => self::shorten(str_replace(['تحويل صناديق — ', 'تحويل صناديق'], '', $raw), 90),
                'badge' => 'تحويل',
            ];
        }

        // ملاحظة المستخدم قبل الجزء الآلي
        if (preg_match('/^(.+?)\s*—\s*(توزيع|استلام|تحويل|مقدم|تحصيل)/u', $raw, $m)
            && mb_strlen(trim($m[1])) >= 2
            && mb_strlen(trim($m[1])) <= 40
        ) {
            return [
                'title' => trim($m[1]),
                'detail' => self::shorten(trim(substr($raw, strlen($m[1]) + 3)), 100),
                'badge' => null,
            ];
        }

        return [
            'title' => self::shorten($raw, 72),
            'detail' => mb_strlen($raw) > 72 ? $raw : null,
            'badge' => null,
        ];
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
