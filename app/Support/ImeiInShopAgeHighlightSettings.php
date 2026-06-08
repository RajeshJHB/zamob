<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Validation\Rule;

final class ImeiInShopAgeHighlightSettings
{
    public const SETTING_KEY = 'imei_in_shop_age_highlight';

    public function __construct(
        public int $band1Days,
        public int $band2Days,
        public int $band3Days,
        public string $tier1Colour,
        public string $tier2Colour,
        public string $tier3Colour,
        public string $tier4Colour,
        public string $missingDateColour,
    ) {}

    public static function defaults(): self
    {
        return new self(
            band1Days: 60,
            band2Days: 30,
            band3Days: 30,
            tier1Colour: ImeiInShopAgeColour::GREEN,
            tier2Colour: ImeiInShopAgeColour::YELLOW,
            tier3Colour: ImeiInShopAgeColour::ORANGE,
            tier4Colour: ImeiInShopAgeColour::RED,
            missingDateColour: ImeiInShopAgeColour::ORANGE,
        );
    }

    public static function load(): self
    {
        $stored = AppSetting::getValue(self::SETTING_KEY);
        if ($stored === null || $stored === '') {
            return self::defaults();
        }

        $decoded = json_decode($stored, true);
        if (! is_array($decoded)) {
            return self::defaults();
        }

        if (isset($decoded['date1']) && ! isset($decoded['band1_days'])) {
            return self::fromLegacyCumulativeEnds($decoded);
        }

        $defaults = self::defaults();

        return new self(
            band1Days: self::intOrDefault($decoded['band1_days'] ?? null, $defaults->band1Days),
            band2Days: self::intOrDefault($decoded['band2_days'] ?? null, $defaults->band2Days),
            band3Days: self::intOrDefault($decoded['band3_days'] ?? null, $defaults->band3Days),
            tier1Colour: self::colourOrDefault($decoded['tier1_colour'] ?? null, $defaults->tier1Colour),
            tier2Colour: self::colourOrDefault($decoded['tier2_colour'] ?? null, $defaults->tier2Colour),
            tier3Colour: self::colourOrDefault($decoded['tier3_colour'] ?? null, $defaults->tier3Colour),
            tier4Colour: self::colourOrDefault($decoded['tier4_colour'] ?? null, $defaults->tier4Colour),
            missingDateColour: self::colourOrDefault($decoded['missing_date_colour'] ?? null, $defaults->missingDateColour),
        );
    }

    public function persist(): void
    {
        AppSetting::setValue(self::SETTING_KEY, json_encode([
            'band1_days' => $this->band1Days,
            'band2_days' => $this->band2Days,
            'band3_days' => $this->band3Days,
            'tier1_colour' => $this->tier1Colour,
            'tier2_colour' => $this->tier2Colour,
            'tier3_colour' => $this->tier3Colour,
            'tier4_colour' => $this->tier4Colour,
            'missing_date_colour' => $this->missingDateColour,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(): array
    {
        return [
            'age_band_1_days' => ['required', 'integer', 'min:1'],
            'age_band_2_days' => ['required', 'integer', 'min:1'],
            'age_band_3_days' => ['required', 'integer', 'min:1'],
            'tier1_colour' => ['required', 'string', Rule::in(ImeiInShopAgeColour::keys())],
            'tier2_colour' => ['required', 'string', Rule::in(ImeiInShopAgeColour::keys())],
            'tier3_colour' => ['required', 'string', Rule::in(ImeiInShopAgeColour::keys())],
            'tier4_colour' => ['required', 'string', Rule::in(ImeiInShopAgeColour::keys())],
            'missing_date_colour' => ['required', 'string', Rule::in(ImeiInShopAgeColour::keys())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function validationMessages(): array
    {
        return [
            'age_band_1_days.min' => 'Band 1 must be at least 1 day.',
            'age_band_2_days.min' => 'Band 2 must be at least 1 day.',
            'age_band_3_days.min' => 'Band 3 must be at least 1 day.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            band1Days: (int) $validated['age_band_1_days'],
            band2Days: (int) $validated['age_band_2_days'],
            band3Days: (int) $validated['age_band_3_days'],
            tier1Colour: (string) $validated['tier1_colour'],
            tier2Colour: (string) $validated['tier2_colour'],
            tier3Colour: (string) $validated['tier3_colour'],
            tier4Colour: (string) $validated['tier4_colour'],
            missingDateColour: (string) $validated['missing_date_colour'],
        );
    }

    public function band1End(): int
    {
        return $this->band1Days;
    }

    public function band2End(): int
    {
        return $this->band1Days + $this->band2Days;
    }

    public function band3End(): int
    {
        return $this->band1Days + $this->band2Days + $this->band3Days;
    }

    public function rangeLabelForTier(int $tier): string
    {
        return match ($tier) {
            1 => '0–'.$this->band1End(),
            2 => ($this->band1End() + 1).'–'.$this->band2End(),
            3 => ($this->band2End() + 1).'–'.$this->band3End(),
            4 => 'Over '.$this->band3End(),
            default => '',
        };
    }

    public function colourKeyForAgeDays(int $ageDays): string
    {
        if ($ageDays <= $this->band1End()) {
            return $this->tier1Colour;
        }

        if ($ageDays <= $this->band2End()) {
            return $this->tier2Colour;
        }

        if ($ageDays <= $this->band3End()) {
            return $this->tier3Colour;
        }

        return $this->tier4Colour;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private static function fromLegacyCumulativeEnds(array $decoded): self
    {
        $defaults = self::defaults();
        $date1 = self::intOrDefault($decoded['date1'] ?? null, $defaults->band1End());
        $date2 = self::intOrDefault($decoded['date2'] ?? null, $defaults->band2End());
        $date3 = self::intOrDefault($decoded['date3'] ?? null, $defaults->band3End());

        return new self(
            band1Days: max(1, $date1),
            band2Days: max(1, $date2 - $date1),
            band3Days: max(1, $date3 - $date2),
            tier1Colour: self::colourOrDefault($decoded['tier1_colour'] ?? null, $defaults->tier1Colour),
            tier2Colour: self::colourOrDefault($decoded['tier2_colour'] ?? null, $defaults->tier2Colour),
            tier3Colour: self::colourOrDefault($decoded['tier3_colour'] ?? null, $defaults->tier3Colour),
            tier4Colour: self::colourOrDefault($decoded['tier4_colour'] ?? null, $defaults->tier4Colour),
            missingDateColour: self::colourOrDefault($decoded['missing_date_colour'] ?? null, $defaults->missingDateColour),
        );
    }

    private static function intOrDefault(mixed $value, int $default): int
    {
        if (! is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }

    private static function colourOrDefault(mixed $value, string $default): string
    {
        if (! is_string($value) || ! ImeiInShopAgeColour::isValid($value)) {
            return $default;
        }

        return $value;
    }
}
