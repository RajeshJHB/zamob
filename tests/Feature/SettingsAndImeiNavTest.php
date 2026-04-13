<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from settings', function () {
    $this->get(route('settings.index'))->assertRedirect(route('login'));
});

test('guests are redirected from add imei form', function () {
    $this->get(route('imeis.create'))->assertRedirect(route('login'));
});

test('verified users can view settings and add imei form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.index'))
        ->assertSuccessful()
        ->assertSee('Make', false)
        ->assertSee('Models', false)
        ->assertDontSee('Change password', false);

    $this->actingAs($user)
        ->get(route('settings.makes.index'))
        ->assertSuccessful()
        ->assertSee('Make', false)
        ->assertSee('All makes', false);

    $this->actingAs($user)
        ->get(route('settings.models.index'))
        ->assertSuccessful()
        ->assertSee('Models', false)
        ->assertSee('Select make', false);

    $this->actingAs($user)
        ->get(route('settings.locations.index'))
        ->assertSuccessful()
        ->assertSee('Locations', false)
        ->assertSee('All locations', false);

    $this->actingAs($user)
        ->get(route('settings.types.index'))
        ->assertSuccessful()
        ->assertSee('Types', false)
        ->assertSee('All types', false);

    $this->actingAs($user)
        ->get(route('settings.status.index'))
        ->assertSuccessful()
        ->assertSee('Status', false)
        ->assertSee('All statuses', false);

    $this->actingAs($user)
        ->get('/settings/unknown-section')
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful();
});
