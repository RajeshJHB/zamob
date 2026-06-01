<?php

namespace App\Support;

use App\Models\AppSetting;

final class ImeiCostIncl
{
    public const SETTING_KEY = 'vat_percent';

    public const DEFAULT_VAT_PERCENT = 15.0;

    public static function vatPercent(): float
    {
        $stored = AppSetting::getValue(self::SETTING_KEY);

        if ($stored === null || $stored === '') {
            return self::DEFAULT_VAT_PERCENT;
        }

        return (float) $stored;
    }

    public static function format(?string $costExcl, ?float $vatPercent = null): ?string
    {
        $amount = self::parseAmount($costExcl);
        if ($amount === null) {
            return null;
        }

        $vat = $vatPercent ?? self::vatPercent();
        $incl = $amount * (1 + ($vat / 100));

        return self::formatAmount($incl);
    }

    public static function parseAmount(?string $costExcl): ?float
    {
        if ($costExcl === null) {
            return null;
        }

        $normalized = str_replace([' ', ','], ['', '.'], trim($costExcl));
        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * @param  iterable<int|string, mixed>  $costExclValues
     */
    public static function sumInclusive(iterable $costExclValues, ?float $vatPercent = null): float
    {
        $vat = $vatPercent ?? self::vatPercent();
        $multiplier = 1 + ($vat / 100);
        $sum = 0.0;

        foreach ($costExclValues as $costExcl) {
            $amount = self::parseAmount(is_string($costExcl) ? $costExcl : null);
            if ($amount !== null) {
                $sum += $amount * $multiplier;
            }
        }

        return $sum;
    }

    public static function formatTotal(float $total): string
    {
        return self::formatAmount($total);
    }

    private static function formatAmount(float $amount): string
    {
        $formatted = number_format($amount, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
