<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

final class Role4Protection
{
    /**
     * @param  list<int|string>  $requestedRoleIds
     * @return list<int>
     */
    public static function ensureRoleFourPreserved(User $user, array $requestedRoleIds): array
    {
        return collect($requestedRoleIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Role>  $roles
     */
    public static function isLockedForUser(User $user, Role $role): bool
    {
        return false;
    }
}
