<?php

use App\Models\AppSetting;
use App\Models\Contact;
use App\Models\User;
use App\Support\BrowseListLimit;
use App\Support\ImeiInShopAgeColour;
use App\Support\ImeiInShopAgeHighlightSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function defaultSettingsPayload(array $overrides = []): array
{
    return array_merge([
        'browse_list_limit' => 75,
        'age_band_1_days' => 60,
        'age_band_2_days' => 30,
        'age_band_3_days' => 30,
        'tier1_colour' => ImeiInShopAgeColour::GREEN,
        'tier2_colour' => ImeiInShopAgeColour::YELLOW,
        'tier3_colour' => ImeiInShopAgeColour::ORANGE,
        'tier4_colour' => ImeiInShopAgeColour::RED,
        'missing_date_colour' => ImeiInShopAgeColour::ORANGE,
    ], $overrides);
}

test('guests are redirected from default settings', function () {
    $this->get(route('settings.default.index'))->assertRedirect(route('login'));
});

test('authenticated user can view default settings read only without role 4', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.default.index'))
        ->assertSuccessful()
        ->assertSee('Default Settings', false)
        ->assertSee('Read-only. Only Role 4 users can change these settings.', false)
        ->assertSee('In Shop row colours', false)
        ->assertSee('1st x Days', false)
        ->assertSee('Next x Days', false)
        ->assertSee('0–60', false)
        ->assertDontSee('Save defaults', false);
});

test('non role 4 users cannot update default settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('settings.default.update'), defaultSettingsPayload())
        ->assertForbidden();
});

test('role 4 user can view and update default settings', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);

    $this->actingAs($user)
        ->get(route('settings.default.index'))
        ->assertSuccessful()
        ->assertSee('Save defaults', false);

    $this->actingAs($user)
        ->put(route('settings.default.update'), defaultSettingsPayload(['browse_list_limit' => 75, 'age_band_1_days' => 45, 'age_band_2_days' => 45, 'age_band_3_days' => 30]))
        ->assertRedirect(route('settings.default.index'))
        ->assertSessionHas('message');

    expect(BrowseListLimit::limit())->toBe(75);

    $ageSettings = ImeiInShopAgeHighlightSettings::load();
    expect($ageSettings->band1Days)->toBe(45)
        ->and($ageSettings->band2Days)->toBe(45)
        ->and($ageSettings->band3Days)->toBe(30)
        ->and($ageSettings->rangeLabelForTier(4))->toBe('Over 120')
        ->and(AppSetting::getValue(ImeiInShopAgeHighlightSettings::SETTING_KEY))->not->toBeNull();
});

test('browse list limit must be within allowed range', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);

    $this->actingAs($user)
        ->put(route('settings.default.update'), defaultSettingsPayload(['browse_list_limit' => 5]))
        ->assertSessionHasErrors('browse_list_limit');

    $this->actingAs($user)
        ->put(route('settings.default.update'), defaultSettingsPayload(['browse_list_limit' => 20001]))
        ->assertSessionHasErrors('browse_list_limit');
});

test('each band duration must be at least one day', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);

    $this->actingAs($user)
        ->put(route('settings.default.update'), defaultSettingsPayload(['age_band_2_days' => 0]))
        ->assertSessionHasErrors('age_band_2_days');
});

test('same band duration may be used for multiple bands', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);

    $this->actingAs($user)
        ->put(route('settings.default.update'), defaultSettingsPayload([
            'age_band_1_days' => 45,
            'age_band_2_days' => 45,
            'age_band_3_days' => 45,
        ]))
        ->assertRedirect(route('settings.default.index'));

    $loaded = ImeiInShopAgeHighlightSettings::load();
    expect($loaded->band1Days)->toBe(45)
        ->and($loaded->band2Days)->toBe(45)
        ->and($loaded->band3Days)->toBe(45)
        ->and($loaded->rangeLabelForTier(2))->toBe('46–90')
        ->and($loaded->rangeLabelForTier(4))->toBe('Over 135');
});

test('same colour may be selected for multiple bands', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);

    $this->actingAs($user)
        ->put(route('settings.default.update'), defaultSettingsPayload([
            'tier1_colour' => ImeiInShopAgeColour::BLUE,
            'tier2_colour' => ImeiInShopAgeColour::BLUE,
            'tier3_colour' => ImeiInShopAgeColour::BLUE,
            'tier4_colour' => ImeiInShopAgeColour::BLUE,
        ]))
        ->assertRedirect(route('settings.default.index'));

    $loaded = ImeiInShopAgeHighlightSettings::load();
    expect($loaded->tier1Colour)->toBe(ImeiInShopAgeColour::BLUE)
        ->and($loaded->tier4Colour)->toBe(ImeiInShopAgeColour::BLUE);
});

test('blank contact search respects configured browse list limit', function () {
    AppSetting::setBrowseListLimit(50);
    $user = User::factory()->create();

    Contact::factory()->count(60)->create();

    $response = $this->actingAs($user)
        ->get(route('contacts.index'))
        ->assertSuccessful()
        ->assertViewHas('listingAll', true);

    expect($response->viewData('contacts'))->toHaveCount(50);
});
