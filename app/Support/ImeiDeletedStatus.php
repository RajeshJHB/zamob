<?php

namespace App\Support;

use App\Models\Imei;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ImeiDeletedStatus
{
    public const VALUE = 'Deleted';

    public static function isDeleted(Imei $imei): bool
    {
        return $imei->status === self::VALUE;
    }

    public static function userCanViewDeleted(?User $user): bool
    {
        return $user?->canDeleteImeiReferenceData() === true;
    }

    /**
     * @param  Builder<Imei>  $query
     */
    public static function applyVisibleScope(Builder $query, ?User $user): void
    {
        if (! self::userCanViewDeleted($user)) {
            $query->where('status', '!=', self::VALUE);
        }
    }

    /**
     * @param  list<string>  $statuses
     * @return list<string>
     */
    public static function appendToStatusPicklist(array $statuses, ?User $user): array
    {
        if (! self::userCanViewDeleted($user)) {
            return array_values(array_filter(
                $statuses,
                fn (string $status): bool => $status !== self::VALUE,
            ));
        }

        if (! in_array(self::VALUE, $statuses, true)) {
            $statuses[] = self::VALUE;
            sort($statuses, SORT_STRING);
        }

        return $statuses;
    }
}
