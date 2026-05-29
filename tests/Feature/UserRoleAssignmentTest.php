<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function assignRolesRole(int $number, string $name): Role
{
    return Role::query()->firstOrCreate(
        ['number' => $number],
        ['name' => $name],
    );
}

function assignRolesManager(): User
{
    $user = User::factory()->create();
    $roleManager = assignRolesRole(1, 'Role Manager');
    $user->roles()->sync([$roleManager->id]);

    return $user;
}

test('role manager can open assign roles index and edit page', function () {
    $manager = assignRolesManager();
    $target = User::factory()->create();
    assignRolesRole(2, 'Role 2');
    assignRolesRole(4, 'Role 4');

    $this->actingAs($manager)
        ->get(route('user-roles.index'))
        ->assertSuccessful()
        ->assertSee('Assign Roles to Users', false)
        ->assertSee('Save all changes', false)
        ->assertSee('Edit', false);

    $this->actingAs($manager)
        ->get(route('user-roles.edit', $target))
        ->assertSuccessful()
        ->assertSee('Edit roles', false)
        ->assertSee($target->email, false);
});

test('role manager can assign role 2 to a user without role 4', function () {
    $manager = assignRolesManager();
    $target = User::factory()->create();
    $roleTwo = assignRolesRole(2, 'Role 2');
    assignRolesRole(4, 'Role 4');

    $this->actingAs($manager)
        ->put(route('user-roles.update', $target), [
            'roles' => [$roleTwo->id],
        ])
        ->assertRedirect(route('user-roles.index'))
        ->assertSessionHas('success');

    expect($target->fresh()->hasRole(2))->toBeTrue();
    expect($target->fresh()->hasRole(4))->toBeFalse();
});

test('role manager can remove role 4 from a user who has it via single user update', function () {
    $manager = assignRolesManager();
    $target = User::factory()->create();
    $roleTwo = assignRolesRole(2, 'Role 2');
    $roleFour = assignRolesRole(4, 'Role 4');
    $target->roles()->sync([$roleTwo->id, $roleFour->id]);

    $this->actingAs($manager)
        ->put(route('user-roles.update', $target), [
            'roles' => [$roleTwo->id],
        ])
        ->assertRedirect(route('user-roles.index'));

    $fresh = $target->fresh();
    expect($fresh->hasRole(2))->toBeTrue();
    expect($fresh->hasRole(4))->toBeFalse();
});

test('role manager can remove role 4 from a user who has it via bulk update', function () {
    $manager = assignRolesManager();
    $target = User::factory()->create();
    $roleTwo = assignRolesRole(2, 'Role 2');
    $roleFour = assignRolesRole(4, 'Role 4');
    $target->roles()->sync([$roleTwo->id, $roleFour->id]);

    $this->actingAs($manager)
        ->post(route('user-roles.bulk-update'), [
            'user_roles' => [
                [
                    'user_id' => $target->id,
                    'roles' => [$roleTwo->id],
                ],
            ],
        ])
        ->assertRedirect(route('user-roles.index'));

    $fresh = $target->fresh();
    expect($fresh->hasRole(2))->toBeTrue();
    expect($fresh->hasRole(4))->toBeFalse();
});

test('edit page does not lock role 4 checkbox for a role 4 user', function () {
    $manager = assignRolesManager();
    $target = User::factory()->create();
    $roleFour = assignRolesRole(4, 'Role 4');
    $target->roles()->sync([$roleFour->id]);

    $html = $this->actingAs($manager)
        ->get(route('user-roles.edit', $target))
        ->assertSuccessful()
        ->getContent();

    expect($html)->not->toContain('Locked — cannot be removed.');
});

test('non role manager cannot access assign roles pages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('user-roles.index'))
        ->assertForbidden();

    $target = User::factory()->create();

    $this->actingAs($user)
        ->get(route('user-roles.edit', $target))
        ->assertForbidden();
});

test('cannot remove the last role manager via single user update', function () {
    $manager = assignRolesManager();
    $roleManager = assignRolesRole(1, 'Role Manager');
    $roleTwo = assignRolesRole(2, 'Role 2');

    $this->actingAs($manager)
        ->put(route('user-roles.update', $manager), [
            'roles' => [$roleTwo->id],
        ])
        ->assertRedirect(route('user-roles.edit', $manager))
        ->assertSessionHas('error');

    expect($manager->fresh()->roles->pluck('id')->all())->toContain($roleManager->id);
});

test('bulk update accepts users with no roles selected', function () {
    $manager = assignRolesManager();
    $withRole = User::factory()->create();
    $withoutRole = User::factory()->create();
    $roleTwo = assignRolesRole(2, 'Role 2');
    $withRole->roles()->sync([$roleTwo->id]);

    $this->actingAs($manager)
        ->post(route('user-roles.bulk-update'), [
            'user_roles' => [
                [
                    'user_id' => $withRole->id,
                    'roles' => [$roleTwo->id],
                ],
                [
                    'user_id' => $withoutRole->id,
                ],
            ],
        ])
        ->assertRedirect(route('user-roles.index'))
        ->assertSessionHas('success');

    expect($withRole->fresh()->hasRole(2))->toBeTrue();
    expect($withoutRole->fresh()->roles)->toBeEmpty();
});

test('cannot remove the last role manager via bulk update', function () {
    $manager = assignRolesManager();
    $roleManager = assignRolesRole(1, 'Role Manager');
    $roleTwo = assignRolesRole(2, 'Role 2');

    $this->actingAs($manager)
        ->post(route('user-roles.bulk-update'), [
            'user_roles' => [
                [
                    'user_id' => $manager->id,
                    'roles' => [$roleTwo->id],
                ],
            ],
        ])
        ->assertRedirect(route('user-roles.index'))
        ->assertSessionHas('error');

    expect($manager->fresh()->roles->pluck('id')->all())->toContain($roleManager->id);
});
