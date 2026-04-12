<?php

use App\Models\ImeiMake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from makes settings', function () {
    $this->get(route('settings.makes.index'))->assertRedirect(route('login'));
    $this->post(route('settings.makes.store'), ['make' => 'Test'])->assertRedirect(route('login'));
});

test('verified users can list makes in alphabetical order', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Zebra']);
    ImeiMake::factory()->create(['make' => 'Apple']);

    $response = $this->actingAs($user)->get(route('settings.makes.index'));

    $response->assertSuccessful();
    $applePos = strpos($response->getContent(), 'Apple');
    $zebraPos = strpos($response->getContent(), 'Zebra');
    expect($applePos)->toBeLessThan($zebraPos);
});

test('verified users can add a make', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.makes.store'), ['make' => '  Samsung  '])
        ->assertRedirect(route('settings.makes.index'));

    expect(ImeiMake::query()->where('make', 'Samsung')->exists())->toBeTrue();
});

test('store rejects duplicate make names', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Nokia']);

    $this->actingAs($user)
        ->post(route('settings.makes.store'), ['make' => 'Nokia'])
        ->assertSessionHasErrors('make');
});

test('verified users can update a make', function () {
    $user = User::factory()->create();
    $make = ImeiMake::factory()->create(['make' => 'Old']);

    $this->actingAs($user)
        ->put(route('settings.makes.update', $make), ['make' => 'New'])
        ->assertRedirect(route('settings.makes.index'));

    expect($make->fresh()->make)->toBe('New');
});

test('update rejects duplicate name from another make', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Taken']);
    $make = ImeiMake::factory()->create(['make' => 'Other']);

    $this->actingAs($user)
        ->put(route('settings.makes.update', $make), ['make' => 'Taken'])
        ->assertSessionHasErrors('make');
});

test('verified users can delete a make', function () {
    $user = User::factory()->create();
    $make = ImeiMake::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.makes.destroy', $make))
        ->assertRedirect(route('settings.makes.index'));

    expect(ImeiMake::query()->find($make->id))->toBeNull();
});
