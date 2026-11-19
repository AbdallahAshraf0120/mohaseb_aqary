<?php

namespace App\Support;

class Phone
{
    /**
     * رقم بصيغة دولية بدون + (مصر افتراضيًا): 01001234567 → 201001234567.
     */
    public static function international(?string $phone, string $countryCode = '20'): ?string
    {
        $digits = preg_replace('/\D+/', '', strtr((string) $phone, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']));
        if ($digits === null || strlen($digits) < 8) {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }
        if (str_starts_with($digits, $countryCode)) {
            return $digits;
        }
        if (str_starts_with($digits, '0')) {
            return $countryCode.substr($digits, 1);
        }

        return $digits;
    }

    public static function whatsappUrl(?string $phone, string $text = ''): ?string
    {
        $intl = self::international($phone);
        if ($intl === null) {
            return null;
        }

        return 'https://wa.me/'.$intl.($text !== '' ? '?text='.rawurlencode($text) : '');
    }

    /**
     * صيغة موحّدة للمقارنة (آخر 10 أرقام) — تُستخدم لاكتشاف العميل المكرر.
     */
    public static function key(?string $phone): ?string
    {
        $intl = self::international($phone);

        return $intl === null ? null : substr($intl, -10);
    }
}
