<?php

use App\Models\ImeiLocation;
use App\Models\ImeiMake;
use App\Models\ImeiModel;
use App\Models\ImeiStatus;
use App\Models\ImeiType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('users without role 4 cannot delete a make', function () {
    $user = User::factory()->create();
    $make = ImeiMake::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.makes.destroy', $make))
        ->assertForbidden();
});

test('users without role 4 cannot delete a model', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Acme']);
    $row = ImeiModel::factory()->create(['make' => 'Acme', 'model' => 'X', 'serial' => '']);

    $this->actingAs($user)
        ->delete(route('settings.models.destroy', $row))
        ->assertForbidden();
});

test('users without role 4 cannot delete a location', function () {
    $user = User::factory()->create();
    $row = ImeiLocation::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.locations.destroy', $row))
        ->assertForbidden();
});

test('users without role 4 cannot delete a type', function () {
    $user = User::factory()->create();
    $row = ImeiType::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.types.destroy', $row))
        ->assertForbidden();
});

test('users without role 4 cannot delete a status', function () {
    $user = User::factory()->create();
    $row = ImeiStatus::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.status.destroy', $row))
        ->assertForbidden();
});
