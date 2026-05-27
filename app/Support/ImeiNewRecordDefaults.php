<?php

namespace App\Support;

final class ImeiNewRecordDefaults
{
    public const LOCATION = 'zaMobile Blairgowrie';

    public const TYPE = 'Vodacom Contract';

    public const STATUS = 'In Shop';

    public const SALE_TYPE = 'None';

    /**
     * @return array<string, string>
     */
    public static function selectFields(): array
    {
        return [
            'location' => self::LOCATION,
            'type' => self::TYPE,
            'status' => self::STATUS,
            'cash_stock_type' => self::SALE_TYPE,
        ];
    }
}
