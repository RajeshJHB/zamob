<?php

namespace App\Support;

use App\Models\AppSetting;

final class BrowseListLimit
{
    public const SETTING_KEY = 'browse_list_limit';

    public const DEFAULT_LIMIT = 200;

    public const MIN_LIMIT = 10;

    public const MAX_LIMIT = 10000;

    public static function limit(): int
    {
        $stored = AppSetting::getValue(self::SETTING_KEY);

        if ($stored === null || $stored === '') {
            return self::DEFAULT_LIMIT;
        }

        $limit = (int) $stored;

        if ($limit < self::MIN_LIMIT) {
            return self::MIN_LIMIT;
        }

        if ($limit > self::MAX_LIMIT) {
            return self::MAX_LIMIT;
        }

        return $limit;
    }
}
