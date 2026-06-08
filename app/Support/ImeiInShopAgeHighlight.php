<?php

namespace App\Support;

use App\Models\Imei;
use Carbon\CarbonInterface;

final class ImeiInShopAgeHighlight
{
    public const IN_SHOP_STATUS = 'In Shop';

    public static function colourKey(Imei $imei, ?CarbonInterface $asOf = null, ?ImeiInShopAgeHighlightSettings $settings = null): ?string
    {
        if ($imei->status !== self::IN_SHOP_STATUS) {
            return null;
        }

        $settings = $settings ?? ImeiInShopAgeHighlightSettings::load();

        if ($imei->date_in === null) {
            return $settings->missingDateColour;
        }

        $asOf = ($asOf ?? now())->copy()->startOfDay();
        $ageDays = $imei->date_in->copy()->startOfDay()->diffInDays($asOf);

        return $settings->colourKeyForAgeDays($ageDays);
    }

    public static function rowClasses(Imei $imei, ?CarbonInterface $asOf = null, ?ImeiInShopAgeHighlightSettings $settings = null): string
    {
        $colourKey = self::colourKey($imei, $asOf, $settings);

        if ($colourKey === null) {
            return 'hover:bg-gray-50';
        }

        return ImeiInShopAgeColour::rowClasses($colourKey);
    }
}
