<?php

use App\Models\ImeiType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from types settings', function () {
    $this->get(route('settings.types.index'))->assertRedirect(route('login'));
    $this->post(route('settings.types.store'), ['type' => 'Test'])->assertRedirect(route('login'));
});

test('verified users can list types in alphabetical order', function () {
    $user = User::factory()->create();
    ImeiType::factory()->create(['type' => 'Zulu']);
    ImeiType::factory()->create(['type' => 'Alpha']);

    $response = $this->actingAs($user)->get(route('settings.types.index'));

    $response->assertSuccessful();
    $alphaPos = strpos($response->getContent(), 'Alpha');
    $zuluPos = strpos($response->getContent(), 'Zulu');
    expect($alphaPos)->toBeLessThan($zuluPos);
});

test('verified users can add a type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.types.store'), ['type' => '  Wholesale  '])
        ->assertRedirect(route('settings.types.index'));

    expect(ImeiType::query()->where('type', 'Wholesale')->exists())->toBeTrue();
});

test('store rejects duplicate types', function () {
    $user = User::factory()->create();
    ImeiType::factory()->create(['type' => 'Retail']);

    $this->actingAs($user)
        ->post(route('settings.types.store'), ['type' => 'Retail'])
        ->assertSessionHasErrors('type');
});

test('verified users can update a type', function () {
    $user = User::factory()->create();
    $row = ImeiType::factory()->create(['type' => 'Old']);

    $this->actingAs($user)
        ->put(route('settings.types.update', $row), ['type' => 'New'])
        ->assertRedirect(route('settings.types.index'));

    expect($row->fresh()->type)->toBe('New');
});

test('update rejects duplicate type from another row', function () {
    $user = User::factory()->create();
    ImeiType::factory()->create(['type' => 'Taken']);
    $row = ImeiType::factory()->create(['type' => 'Other']);

    $this->actingAs($user)
        ->put(route('settings.types.update', $row), ['type' => 'Taken'])
        ->assertSessionHasErrors('type');
});

test('verified users can delete a type', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    $row = ImeiType::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.types.destroy', $row))
        ->assertRedirect(route('settings.types.index'));

    expect(ImeiType::query()->find($row->id))->toBeNull();
});
