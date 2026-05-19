<?php

namespace App\Support;

final class ImeiOptionalStringFields
{
    /**
     * Columns on the legacy `imei` table that are NOT NULL but treated as optional in forms.
     * Blank input must be stored as an empty string, not SQL NULL.
     *
     * @var list<string>
     */
    public const KEYS = [
        'make',
        'model',
        'sn',
        'location',
        'type',
        'status',
        'notes',
        'phonenumber',
        'ref',
        'item_code',
        'ourON',
        'salesON',
        'cost_excl',
    ];
}
