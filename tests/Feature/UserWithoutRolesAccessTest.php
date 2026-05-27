<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user without roles only sees user menu in navigation', function () {
    $user = User::factory()->withoutRoles()->create();

    $html = $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('id="user-menu-button"')
        ->and($html)->toContain('Profile')
        ->and($html)->toContain('Password Reset')
        ->and($html)->toContain('Help')
        ->and($html)->not->toContain('id="settings-menu-button"')
        ->and($html)->not->toContain('>Home</')
        ->and($html)->not->toContain('>IMEI</')
        ->and($html)->not->toContain('>Contacts</')
        ->and($html)->not->toContain('>Notes</');
});

test('user without roles cannot access dashboard or imei routes', function () {
    $user = User::factory()->withoutRoles()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('error');

    $this->actingAs($user)
        ->get(route('imeis.index'))
        ->assertRedirect(route('profile.show'));

    $this->actingAs($user)
        ->get(route('settings.index'))
        ->assertRedirect(route('profile.show'));
});

test('user without roles can access profile help and logout', function () {
    $user = User::factory()->withoutRoles()->create();

    $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertSuccessful();

    $this->actingAs($user)
        ->get(route('help.index'))
        ->assertSuccessful();
});

test('login redirects user without roles to profile', function () {
    $user = User::factory()->withoutRoles()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('info');
});
