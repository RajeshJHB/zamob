<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('help page is available to verified users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('help.index'))
        ->assertSuccessful()
        ->assertSee('Help', false)
        ->assertSee('Version', false);
});

test('help page hides update history for non role 4 users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('help.index'))
        ->assertSuccessful()
        ->assertDontSee('Update History (Role 4)', false);
});

test('help page shows update history for role 4 users', function () {
    $role4 = Role::query()->create([
        'number' => 4,
        'name' => 'Role 4',
    ]);

    $user = User::factory()->create();
    $user->roles()->syncWithoutDetaching([$role4->id]);

    $this->actingAs($user)
        ->get(route('help.index'))
        ->assertSuccessful()
        ->assertSee('Update History (Role 4)', false);
});
