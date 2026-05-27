<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from settings', function () {
    $this->get(route('settings.index'))->assertRedirect(route('login'));
});

test('login page does not show authenticated app menu for guests', function () {
    $html = $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Login', false)
        ->assertSee('Register', false)
        ->assertDontSee('id="settings-menu-button"', false)
        ->getContent();

    expect($html)->not->toContain('>IMEI</')
        ->and($html)->not->toContain('>Contacts</')
        ->and($html)->not->toContain('>Notes</');
});

test('guests are redirected from add imei form', function () {
    $this->get(route('imeis.create'))->assertRedirect(route('login'));
});

test('verified users can view settings and add imei form', function () {
    $user = User::factory()->create();

    $settingsHtml = $this->actingAs($user)
        ->get(route('settings.index'))
        ->assertSuccessful()
        ->assertSee('Make', false)
        ->assertSee('Models', false)
        ->assertDontSee('Sale types', false)
        ->assertDontSee('Change password', false)
        ->getContent();

    expect($settingsHtml)->not->toContain('w-full max-w-none');

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
        ->get(route('settings.sale-types.index'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get('/settings/unknown-section')
        ->assertNotFound();

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('imeis.create', ['embedded' => 1]))
        ->assertSuccessful()
        ->assertSee('Add IMEI', false)
        ->assertDontSee('id="settings-menu-button"', false)
        ->assertDontSee('id="user-menu-button"', false);
});

test('role 4 users can access sale type settings from imei settings', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);

    $this->actingAs($user)
        ->get(route('settings.index'))
        ->assertSuccessful()
        ->assertSee('Sale types', false);

    $this->actingAs($user)
        ->get(route('settings.sale-types.index'))
        ->assertSuccessful()
        ->assertSee('Sale types', false);
});
