<?php

namespace App\Support;

use App\Models\Imei;
use App\Models\User;

final class ImeiNormalizedLookup
{
    public const DELETED_IMEI_MESSAGE = 'Cannot add this deleted used IMEI, get administrator to restore this deleted used IMEI.';

    public const DELETED_IMEI_ROLE4_MESSAGE = 'This is a deleted IMEI. It cannot be added again. Open the record below to review or restore it.';

    public static function find(string $normalizedKey): ?Imei
    {
        if ($normalizedKey === '') {
            return null;
        }

        return Imei::query()->whereNormalizedImei($normalizedKey)->first();
    }

    public static function deletedImeiMessage(?User $user = null): string
    {
        $user ??= auth()->user();

        if (ImeiDeletedStatus::userCanViewDeleted($user)) {
            return self::DELETED_IMEI_ROLE4_MESSAGE;
        }

        return self::DELETED_IMEI_MESSAGE;
    }
}
