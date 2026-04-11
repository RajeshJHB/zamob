<?php

namespace App\Support;

class ImeiValidator
{
    /**
     * Maximum length for non-standard IMEI values after {@see self::normalizeNonStandard()} (trim + strip spaces, dashes, slashes).
     */
    public const MAX_NON_STANDARD_IMEI_LENGTH = 255;

    public static function normalizeDigits(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        return preg_replace('/\D/', '', $input) ?? '';
    }

    public static function isValidChecksum(?string $input): bool
    {
        $digits = self::normalizeDigits($input);
        if (strlen($digits) !== 15) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 14; $i++) {
            $n = (int) $digits[$i];
            if ($i % 2 === 1) {
                $n *= 2;
            }
            $sum += array_sum(str_split((string) $n));
        }

        $check = (10 - ($sum % 10)) % 10;

        return $check === (int) $digits[14];
    }

    /**
     * Normalize a non-standard IMEI for storage and matching: trim, remove spaces, dashes, and slashes.
     * Other characters (letters, digits, symbols) are kept. Must stay in sync with {@see Imei::scopeWhereNormalizedImei()}.
     */
    public static function normalizeNonStandard(?string $input): string
    {
        if ($input === null) {
            return '';
        }

        $s = trim($input);
        $s = str_replace("\0", '', $s);

        return str_replace([' ', '-', '/'], '', $s);
    }
}
