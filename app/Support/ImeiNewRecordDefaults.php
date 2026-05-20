<?php

namespace App\Support;

final class ImeiNewRecordDefaults
{
    public const LOCATION = 'zaMobile Blairgowrie';

    public const TYPE = 'Vodacom Contract';

    public const STATUS = 'In Shop';

    /**
     * @return array<string, string>
     */
    public static function selectFields(): array
    {
        return [
            'location' => self::LOCATION,
            'type' => self::TYPE,
            'status' => self::STATUS,
        ];
    }
}
