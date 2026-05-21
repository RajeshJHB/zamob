<?php

namespace App\Models;

use App\Support\ImeiCostIncl;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key): ?string
    {
        $row = self::query()->where('key', $key)->first();

        return $row?->value;
    }

    public static function setValue(string $key, string $value): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    public static function setVatPercent(float $percent): void
    {
        self::setValue(ImeiCostIncl::SETTING_KEY, (string) $percent);
    }
}
