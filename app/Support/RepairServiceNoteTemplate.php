<?php

namespace App\Support;

final class RepairServiceNoteTemplate
{
    public const TYPE_NAME = 'Repair';

    public const HEADING = 'Repair';

    public const BODY = <<<'TEXT'
IMEI: 
Make:
Model:
Problem:
Price:
Repair Place: 
TEXT;

    public static function isRepairTypeName(string $name): bool
    {
        return strcasecmp(trim($name), self::TYPE_NAME) === 0;
    }
}
