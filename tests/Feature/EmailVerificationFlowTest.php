<?php

use App\Models\User;
use App\Notifications\VerifyEmail;
use App\Support\EmailVerificationToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function verificationUrlFor(User $user, ?string $origin = null): string
{
    $token = EmailVerificationToken::issue($user);

    $path = route('verification.verify', [
        'id' => $user->id,
        'token' => $token,
    ], false);

    $origin ??= rtrim((string) config('app.url'), '/');

    return $origin.$path;
}

test('verification notice redirects verified user without roles to profile', function () {
    $user = User::factory()->withoutRoles()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('info');
});

test('verification notice shows resend cooldown immediately after registration', function () {
    Notification::fake();

    $this->post(route('register'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('verification.notice'));

    $html = $this->get(route('verification.notice'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toMatch('/id="verification-resend-button"[^>]*disabled/')
        ->and($html)->toContain('verification-resend-countdown');
});

test('resend verification email is blocked during cooldown with a message', function () {
    Notification::fake();

    $user = User::factory()->unverified()->withoutRoles()->create();

    $this->actingAs($user)
        ->withSession(['verification_link_sent_at' => now()->timestamp])
        ->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('error');

    Notification::assertNothingSent();
});

test('resend verification email works after cooldown', function () {
    Notification::fake();

    $user = User::factory()->unverified()->withoutRoles()->create();

    $this->actingAs($user)
        ->withSession(['verification_link_sent_at' => now()->subSeconds(61)->timestamp])
        ->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('login redirects verified user without roles to profile with administrator message', function () {
    $user = User::factory()->withoutRoles()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('info');

    $html = $this->actingAs($user)
        ->get(route('profile.show'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('contact an administrator')
        ->and($html)->not->toContain('Resend Verification Email');
});

test('login redirects unverified user to verification notice', function () {
    $user = User::factory()->unverified()->withoutRoles()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('verification.notice'));
});

test('verification link sets email_verified_at without an existing session', function () {
    $user = User::factory()->unverified()->withoutRoles()->create();

    expect($user->email_verified_at)->toBeNull();

    $this->get(verificationUrlFor($user))
        ->assertRedirect(route('profile.show'))
        ->assertSessionHas('info');

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('verification link works when opened on a different host than app url', function () {
    $user = User::factory()->unverified()->withoutRoles()->create();

    $this->get(verificationUrlFor($user, 'http://zamob.test'))
        ->assertRedirect(route('profile.show'));

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('expired verification token is rejected', function () {
    $user = User::factory()->unverified()->withoutRoles()->create();
    $token = EmailVerificationToken::issue($user);

    \Illuminate\Support\Facades\Cache::forget('email-verification:'.$user->id);

    $path = route('verification.verify', ['id' => $user->id, 'token' => $token], false);

    $this->get(config('app.url').$path)->assertForbidden();
});

test('login honors intended verification url and marks email verified', function () {
    $user = User::factory()->unverified()->withoutRoles()->create();
    $verificationUrl = verificationUrlFor($user);

    $this->withSession(['url.intended' => $verificationUrl])
        ->followingRedirects()
        ->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertSuccessful();

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});
