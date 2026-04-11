<?php

namespace App\Support;

class ImeiValidator
{
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
}
