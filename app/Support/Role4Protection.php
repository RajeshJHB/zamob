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
        $requested = collect($requestedRoleIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        if (! $user->hasRole(4)) {
            return $requested->all();
        }

        $roleFourId = Role::query()->where('number', 4)->value('id');

        if ($roleFourId === null) {
            return $requested->all();
        }

        $roleFourId = (int) $roleFourId;

        if (! $requested->contains($roleFourId)) {
            $requested->push($roleFourId);
        }

        return $requested->all();
    }

    /**
     * @param  Collection<int, Role>  $roles
     */
    public static function isLockedForUser(User $user, Role $role): bool
    {
        return $role->number === 4 && $user->hasRole(4);
    }
}
