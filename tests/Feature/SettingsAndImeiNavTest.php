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
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful();
});
