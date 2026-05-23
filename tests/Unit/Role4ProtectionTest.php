<?php

use App\Models\Role;
use App\Models\User;
use App\Support\Role4Protection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function roleByNumber(int $number, string $name): Role
{
    return Role::query()->firstOrCreate(
        ['number' => $number],
        ['name' => $name],
    );
}

test('role 4 is re-added when missing from requested role ids for a role 4 user', function () {
    $user = User::factory()->create();
    $roleTwo = roleByNumber(2, 'Role 2');
    $roleFour = roleByNumber(4, 'Role 4');
    $user->roles()->sync([$roleTwo->id, $roleFour->id]);

    $result = Role4Protection::ensureRoleFourPreserved($user, [$roleTwo->id]);

    expect($result)->toContain($roleTwo->id)
        ->toContain($roleFour->id);
});

test('role 4 is not forced onto users who do not have role 4', function () {
    $user = User::factory()->create();
    $roleTwo = roleByNumber(2, 'Role 2');
    roleByNumber(4, 'Role 4');
    $user->roles()->sync([$roleTwo->id]);

    $result = Role4Protection::ensureRoleFourPreserved($user, [$roleTwo->id]);

    expect($result)->toBe([$roleTwo->id]);
});

test('role 4 is locked in the ui when the user already has role 4', function () {
    $user = User::factory()->create();
    $roleFour = roleByNumber(4, 'Role 4');
    $user->roles()->sync([$roleFour->id]);

    expect(Role4Protection::isLockedForUser($user, $roleFour))->toBeTrue();
});

test('role 4 is not locked when the user does not have role 4', function () {
    $user = User::factory()->create();
    $roleFour = roleByNumber(4, 'Role 4');

    expect(Role4Protection::isLockedForUser($user, $roleFour))->toBeFalse();
});
