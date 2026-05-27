<?php

use App\Models\ImeiSaleType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from sale types settings', function () {
    $this->get(route('settings.sale-types.index'))->assertRedirect(route('login'));
    $this->post(route('settings.sale-types.store'), ['sale_type' => 'Test'])->assertRedirect(route('login'));
});

test('migration seeds default sale types', function () {
    expect(ImeiSaleType::query()->pluck('sale_type')->all())->toBe([
        'None',
        'Cash',
        'Voda_Sale',
        'Easy20wn',
    ]);
});

test('verified users can list sale types in alphabetical order', function () {
    $user = User::factory()->create();
    ImeiSaleType::factory()->create(['sale_type' => 'Zulu']);
    ImeiSaleType::factory()->create(['sale_type' => 'Alpha']);

    $response = $this->actingAs($user)->get(route('settings.sale-types.index'));

    $response->assertForbidden();
});

test('role 4 users can list sale types in alphabetical order', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    ImeiSaleType::factory()->create(['sale_type' => 'Zulu']);
    ImeiSaleType::factory()->create(['sale_type' => 'Alpha']);

    $response = $this->actingAs($user)->get(route('settings.sale-types.index'));

    $response->assertSuccessful();
    $alphaPos = strpos($response->getContent(), 'Alpha');
    $zuluPos = strpos($response->getContent(), 'Zulu');
    expect($alphaPos)->toBeLessThan($zuluPos);
});

test('verified users can add a sale type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.sale-types.store'), ['sale_type' => '  Promo  '])
        ->assertForbidden();

    expect(ImeiSaleType::query()->where('sale_type', 'Promo')->exists())->toBeFalse();
});

test('store rejects duplicate sale types', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.sale-types.store'), ['sale_type' => 'Cash'])
        ->assertForbidden();
});

test('verified users can update a sale type', function () {
    $user = User::factory()->create();
    $row = ImeiSaleType::factory()->create(['sale_type' => 'Old']);

    $this->actingAs($user)
        ->put(route('settings.sale-types.update', $row), ['sale_type' => 'New'])
        ->assertForbidden();

    expect($row->fresh()->sale_type)->toBe('Old');
});

test('role 4 users can add and update a sale type', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);

    $this->actingAs($user)
        ->post(route('settings.sale-types.store'), ['sale_type' => '  Promo  '])
        ->assertRedirect(route('settings.sale-types.index'));

    expect(ImeiSaleType::query()->where('sale_type', 'Promo')->exists())->toBeTrue();

    $row = ImeiSaleType::query()->where('sale_type', 'Promo')->firstOrFail();

    $this->actingAs($user)
        ->put(route('settings.sale-types.update', $row), ['sale_type' => 'New'])
        ->assertRedirect(route('settings.sale-types.index'));

    expect($row->fresh()->sale_type)->toBe('New');
});

test('verified users can delete a sale type', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    $row = ImeiSaleType::factory()->create(['sale_type' => 'Temp']);

    $this->actingAs($user)
        ->delete(route('settings.sale-types.destroy', $row))
        ->assertRedirect(route('settings.sale-types.index'));

    expect(ImeiSaleType::query()->find($row->id))->toBeNull();
});
