<?php

use App\Models\AppSetting;
use App\Models\Contact;
use App\Models\User;
use App\Support\BrowseListLimit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from default settings', function () {
    $this->get(route('settings.default.index'))->assertRedirect(route('login'));
});

test('authenticated user can view and update browse list limit', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.default.index'))
        ->assertSuccessful()
        ->assertSee('Default Settings', false)
        ->assertSee('Find IMEI', false)
        ->assertSee('Contacts', false);

    $this->actingAs($user)
        ->put(route('settings.default.update'), ['browse_list_limit' => 75])
        ->assertRedirect(route('settings.default.index'))
        ->assertSessionHas('message');

    expect(BrowseListLimit::limit())->toBe(75);
    expect(AppSetting::getValue(BrowseListLimit::SETTING_KEY))->toBe('75');
});

test('browse list limit must be within allowed range', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('settings.default.update'), ['browse_list_limit' => 5])
        ->assertSessionHasErrors('browse_list_limit');

    $this->actingAs($user)
        ->put(route('settings.default.update'), ['browse_list_limit' => 20001])
        ->assertSessionHasErrors('browse_list_limit');
});

test('blank contact search respects configured browse list limit', function () {
    AppSetting::setBrowseListLimit(50);
    $user = User::factory()->create();

    Contact::factory()->count(60)->create();

    $response = $this->actingAs($user)
        ->get(route('contacts.search'))
        ->assertSuccessful()
        ->assertViewHas('listingAll', true);

    expect($response->viewData('contacts'))->toHaveCount(50);
});
