<?php

namespace App\Support;

final class ImeiTextLimits
{
    /**
     * Both `notes` (Customer Details) and `ref` (Deal Details) are MySQL TEXT on the imei table.
     */
    public const TEXT_FIELD_MAX = 65535;

    public const CUSTOMER_DETAILS_MAX = self::TEXT_FIELD_MAX;

    public const DEAL_DETAILS_MAX = self::TEXT_FIELD_MAX;
}
