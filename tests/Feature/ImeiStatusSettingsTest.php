<?php

use App\Models\ImeiStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from status settings', function () {
    $this->get(route('settings.status.index'))->assertRedirect(route('login'));
    $this->post(route('settings.status.store'), ['status' => 'Test'])->assertRedirect(route('login'));
});

test('verified users can list statuses in alphabetical order', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'Zulu']);
    ImeiStatus::factory()->create(['status' => 'Alpha']);

    $response = $this->actingAs($user)->get(route('settings.status.index'));

    $response->assertSuccessful();
    $alphaPos = strpos($response->getContent(), 'Alpha');
    $zuluPos = strpos($response->getContent(), 'Zulu');
    expect($alphaPos)->toBeLessThan($zuluPos);
});

test('verified users can add a status', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.status.store'), ['status' => '  Active  '])
        ->assertRedirect(route('settings.status.index'));

    expect(ImeiStatus::query()->where('status', 'Active')->exists())->toBeTrue();
});

test('store rejects duplicate statuses', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'Sold']);

    $this->actingAs($user)
        ->post(route('settings.status.store'), ['status' => 'Sold'])
        ->assertSessionHasErrors('status');
});

test('verified users can update a status', function () {
    $user = User::factory()->create();
    $row = ImeiStatus::factory()->create(['status' => 'Old']);

    $this->actingAs($user)
        ->put(route('settings.status.update', $row), ['status' => 'New'])
        ->assertRedirect(route('settings.status.index'));

    expect($row->fresh()->status)->toBe('New');
});

test('update rejects duplicate status from another row', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'Taken']);
    $row = ImeiStatus::factory()->create(['status' => 'Other']);

    $this->actingAs($user)
        ->put(route('settings.status.update', $row), ['status' => 'Taken'])
        ->assertSessionHasErrors('status');
});

test('verified users can delete a status', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    $row = ImeiStatus::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.status.destroy', $row))
        ->assertRedirect(route('settings.status.index'));

    expect(ImeiStatus::query()->find($row->id))->toBeNull();
});
