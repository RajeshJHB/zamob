<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

final class UserRoleTable
{
    /**
     * @return array{users: Collection<int, User>, roles: Collection<int, Role>}
     */
    public static function indexData(): array
    {
        return [
            'users' => User::query()
                ->with('roles')
                ->whereNotNull('email_verified_at')
                ->orderBy('id')
                ->get(),
            'roles' => Role::query()->orderBy('number')->get(),
        ];
    }
}
