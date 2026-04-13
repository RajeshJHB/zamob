<?php

use App\Models\ImeiLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from locations settings', function () {
    $this->get(route('settings.locations.index'))->assertRedirect(route('login'));
    $this->post(route('settings.locations.store'), ['location' => 'Test'])->assertRedirect(route('login'));
});

test('verified users can list locations in alphabetical order', function () {
    $user = User::factory()->create();
    ImeiLocation::factory()->create(['location' => 'Zebra St']);
    ImeiLocation::factory()->create(['location' => 'Alpha Rd']);

    $response = $this->actingAs($user)->get(route('settings.locations.index'));

    $response->assertSuccessful();
    $alphaPos = strpos($response->getContent(), 'Alpha Rd');
    $zebraPos = strpos($response->getContent(), 'Zebra St');
    expect($alphaPos)->toBeLessThan($zebraPos);
});

test('verified users can add a location', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.locations.store'), ['location' => '  Dock 3  '])
        ->assertRedirect(route('settings.locations.index'));

    expect(ImeiLocation::query()->where('location', 'Dock 3')->exists())->toBeTrue();
});

test('store rejects duplicate locations', function () {
    $user = User::factory()->create();
    ImeiLocation::factory()->create(['location' => 'Main']);

    $this->actingAs($user)
        ->post(route('settings.locations.store'), ['location' => 'Main'])
        ->assertSessionHasErrors('location');
});

test('verified users can update a location', function () {
    $user = User::factory()->create();
    $row = ImeiLocation::factory()->create(['location' => 'Old']);

    $this->actingAs($user)
        ->put(route('settings.locations.update', $row), ['location' => 'New'])
        ->assertRedirect(route('settings.locations.index'));

    expect($row->fresh()->location)->toBe('New');
});

test('update rejects duplicate location from another row', function () {
    $user = User::factory()->create();
    ImeiLocation::factory()->create(['location' => 'Taken']);
    $row = ImeiLocation::factory()->create(['location' => 'Other']);

    $this->actingAs($user)
        ->put(route('settings.locations.update', $row), ['location' => 'Taken'])
        ->assertSessionHasErrors('location');
});

test('verified users can delete a location', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    $row = ImeiLocation::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.locations.destroy', $row))
        ->assertRedirect(route('settings.locations.index'));

    expect(ImeiLocation::query()->find($row->id))->toBeNull();
});
