<?php

use App\Models\AppSetting;
use App\Models\User;
use App\Support\ImeiCostIncl;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from vat settings', function () {
    $this->get(route('settings.vat.index'))->assertRedirect(route('login'));
});

test('imei cost incl uses saved vat percent from settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('settings.vat.update'), ['vat_percent' => 15])
        ->assertRedirect();

    expect(ImeiCostIncl::format('100'))->toBe('115');
});

test('authenticated user can view and update vat percent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.vat.index'))
        ->assertSuccessful()
        ->assertSee('VAT Settings', false);

    $this->actingAs($user)
        ->put(route('settings.vat.update'), ['vat_percent' => 15.5])
        ->assertRedirect(route('settings.vat.index'))
        ->assertSessionHas('message');

    expect(ImeiCostIncl::vatPercent())->toBe(15.5);
    expect(AppSetting::getValue(ImeiCostIncl::SETTING_KEY))->toBe('15.5');
});

test('vat percent must be between 0 and 100', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('settings.vat.update'), ['vat_percent' => 101])
        ->assertSessionHasErrors('vat_percent');

    $this->actingAs($user)
        ->put(route('settings.vat.update'), ['vat_percent' => -1])
        ->assertSessionHasErrors('vat_percent');
});
