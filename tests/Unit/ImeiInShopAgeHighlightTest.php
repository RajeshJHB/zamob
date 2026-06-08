<?php

use App\Models\AppSetting;
use App\Models\Imei;
use App\Support\ImeiInShopAgeColour;
use App\Support\ImeiInShopAgeHighlight;
use App\Support\ImeiInShopAgeHighlightSettings;
use Carbon\Carbon;

test('non in shop records have no age highlight colour', function () {
    $imei = new Imei([
        'status' => 'Sold',
        'date_in' => now()->subDays(120),
    ]);

    expect(ImeiInShopAgeHighlight::colourKey($imei))->toBeNull()
        ->and(ImeiInShopAgeHighlight::rowClasses($imei))->toBe('hover:bg-gray-50');
});

test('in shop records without date in use configured missing date colour', function () {
    $settings = new ImeiInShopAgeHighlightSettings(
        band1Days: 45,
        band2Days: 45,
        band3Days: 30,
        tier1Colour: ImeiInShopAgeColour::GREEN,
        tier2Colour: ImeiInShopAgeColour::YELLOW,
        tier3Colour: ImeiInShopAgeColour::ORANGE,
        tier4Colour: ImeiInShopAgeColour::RED,
        missingDateColour: ImeiInShopAgeColour::PINK,
    );

    $imei = new Imei([
        'status' => ImeiInShopAgeHighlight::IN_SHOP_STATUS,
        'date_in' => null,
    ]);

    expect(ImeiInShopAgeHighlight::colourKey($imei, null, $settings))->toBe(ImeiInShopAgeColour::PINK)
        ->and(ImeiInShopAgeHighlight::rowClasses($imei, null, $settings))->toBe('bg-pink-100 hover:bg-pink-200');
});

test('in shop age bands use accumulated day durations from band lengths', function () {
    $asOf = Carbon::parse('2026-05-28 15:00:00');
    $settings = new ImeiInShopAgeHighlightSettings(
        band1Days: 45,
        band2Days: 45,
        band3Days: 30,
        tier1Colour: ImeiInShopAgeColour::GREEN,
        tier2Colour: ImeiInShopAgeColour::YELLOW,
        tier3Colour: ImeiInShopAgeColour::ORANGE,
        tier4Colour: ImeiInShopAgeColour::RED,
        missingDateColour: ImeiInShopAgeColour::ORANGE,
    );

    expect($settings->rangeLabelForTier(1))->toBe('0–45')
        ->and($settings->rangeLabelForTier(2))->toBe('46–90')
        ->and($settings->rangeLabelForTier(3))->toBe('91–120')
        ->and($settings->rangeLabelForTier(4))->toBe('Over 120');

    $band1End = new Imei([
        'status' => ImeiInShopAgeHighlight::IN_SHOP_STATUS,
        'date_in' => $asOf->copy()->subDays(45),
    ]);
    $band2Start = new Imei([
        'status' => ImeiInShopAgeHighlight::IN_SHOP_STATUS,
        'date_in' => $asOf->copy()->subDays(46),
    ]);
    $band2End = new Imei([
        'status' => ImeiInShopAgeHighlight::IN_SHOP_STATUS,
        'date_in' => $asOf->copy()->subDays(90),
    ]);
    $band3Start = new Imei([
        'status' => ImeiInShopAgeHighlight::IN_SHOP_STATUS,
        'date_in' => $asOf->copy()->subDays(91),
    ]);
    $band4Start = new Imei([
        'status' => ImeiInShopAgeHighlight::IN_SHOP_STATUS,
        'date_in' => $asOf->copy()->subDays(121),
    ]);

    expect(ImeiInShopAgeHighlight::colourKey($band1End, $asOf, $settings))->toBe(ImeiInShopAgeColour::GREEN)
        ->and(ImeiInShopAgeHighlight::colourKey($band2Start, $asOf, $settings))->toBe(ImeiInShopAgeColour::YELLOW)
        ->and(ImeiInShopAgeHighlight::colourKey($band2End, $asOf, $settings))->toBe(ImeiInShopAgeColour::YELLOW)
        ->and(ImeiInShopAgeHighlight::colourKey($band3Start, $asOf, $settings))->toBe(ImeiInShopAgeColour::ORANGE)
        ->and(ImeiInShopAgeHighlight::colourKey($band4Start, $asOf, $settings))->toBe(ImeiInShopAgeColour::RED);
});

test('settings persist and reload from app settings', function () {
    $settings = new ImeiInShopAgeHighlightSettings(
        band1Days: 7,
        band2Days: 14,
        band3Days: 730,
        tier1Colour: ImeiInShopAgeColour::BLUE,
        tier2Colour: ImeiInShopAgeColour::BLUE,
        tier3Colour: ImeiInShopAgeColour::GRAY,
        tier4Colour: ImeiInShopAgeColour::RED,
        missingDateColour: ImeiInShopAgeColour::ORANGE,
    );

    $settings->persist();

    $loaded = ImeiInShopAgeHighlightSettings::load();

    expect($loaded->band1Days)->toBe(7)
        ->and($loaded->band2Days)->toBe(14)
        ->and($loaded->band3Days)->toBe(730)
        ->and($loaded->tier1Colour)->toBe(ImeiInShopAgeColour::BLUE)
        ->and($loaded->colourKeyForAgeDays(10))->toBe(ImeiInShopAgeColour::BLUE)
        ->and($loaded->colourKeyForAgeDays(15))->toBe(ImeiInShopAgeColour::BLUE)
        ->and($loaded->colourKeyForAgeDays(22))->toBe(ImeiInShopAgeColour::GRAY)
        ->and($loaded->colourKeyForAgeDays(752))->toBe(ImeiInShopAgeColour::RED);
});

test('legacy cumulative date settings are converted to band durations on load', function () {
    AppSetting::setValue(ImeiInShopAgeHighlightSettings::SETTING_KEY, json_encode([
        'date1' => 60,
        'date2' => 90,
        'date3' => 120,
        'tier1_colour' => ImeiInShopAgeColour::GREEN,
        'tier2_colour' => ImeiInShopAgeColour::YELLOW,
        'tier3_colour' => ImeiInShopAgeColour::ORANGE,
        'tier4_colour' => ImeiInShopAgeColour::RED,
        'missing_date_colour' => ImeiInShopAgeColour::ORANGE,
    ], JSON_THROW_ON_ERROR));

    $loaded = ImeiInShopAgeHighlightSettings::load();

    expect($loaded->band1Days)->toBe(60)
        ->and($loaded->band2Days)->toBe(30)
        ->and($loaded->band3Days)->toBe(30)
        ->and($loaded->rangeLabelForTier(2))->toBe('61–90');
});
