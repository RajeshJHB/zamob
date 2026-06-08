<?php

namespace App\Support;

final class ImeiInShopAgeColour
{
    public const GREEN = 'green';

    public const YELLOW = 'yellow';

    public const ORANGE = 'orange';

    public const RED = 'red';

    public const BLUE = 'blue';

    public const PURPLE = 'purple';

    public const PINK = 'pink';

    public const GRAY = 'gray';

    /**
     * @return array<string, array{label: string, row: string, swatch: string}>
     */
    public static function options(): array
    {
        return [
            self::GREEN => [
                'label' => 'Green',
                'row' => 'bg-green-100 hover:bg-green-200',
                'swatch' => 'bg-green-200',
            ],
            self::YELLOW => [
                'label' => 'Yellow',
                'row' => 'bg-yellow-100 hover:bg-yellow-200',
                'swatch' => 'bg-yellow-200',
            ],
            self::ORANGE => [
                'label' => 'Orange',
                'row' => 'bg-orange-100 hover:bg-orange-200',
                'swatch' => 'bg-orange-200',
            ],
            self::RED => [
                'label' => 'Red',
                'row' => 'bg-red-200 hover:bg-red-300',
                'swatch' => 'bg-red-300',
            ],
            self::BLUE => [
                'label' => 'Blue',
                'row' => 'bg-blue-100 hover:bg-blue-200',
                'swatch' => 'bg-blue-200',
            ],
            self::PURPLE => [
                'label' => 'Purple',
                'row' => 'bg-purple-100 hover:bg-purple-200',
                'swatch' => 'bg-purple-200',
            ],
            self::PINK => [
                'label' => 'Pink',
                'row' => 'bg-pink-100 hover:bg-pink-200',
                'swatch' => 'bg-pink-200',
            ],
            self::GRAY => [
                'label' => 'Gray',
                'row' => 'bg-gray-100 hover:bg-gray-200',
                'swatch' => 'bg-gray-200',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    public static function isValid(string $key): bool
    {
        return array_key_exists($key, self::options());
    }

    public static function rowClasses(string $key): string
    {
        return self::options()[$key]['row'] ?? self::options()[self::GREEN]['row'];
    }

    public static function label(string $key): string
    {
        return self::options()[$key]['label'] ?? ucfirst($key);
    }
}
