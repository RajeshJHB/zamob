<?php

use App\Models\Contact;
use App\Models\ContactCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected from contact category settings', function () {
    $this->get(route('settings.contact-categories.index'))->assertRedirect(route('login'));
    $this->post(route('settings.contact-categories.store'), ['name' => 'Test'])->assertRedirect(route('login'));
});

test('verified users can list contact categories in sort order', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.contact-categories.index'));

    $response->assertSuccessful();
    $customerPos = strpos($response->getContent(), 'Customer');
    $supplierPos = strpos($response->getContent(), 'Supplier');
    $emergencyPos = strpos($response->getContent(), 'Emergency');
    expect($customerPos)->toBeLessThan($supplierPos);
    expect($supplierPos)->toBeLessThan($emergencyPos);
});

test('verified users can add a contact category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.contact-categories.store'), ['name' => '  Courier  '])
        ->assertRedirect(route('settings.contact-categories.index'));

    expect(ContactCategory::query()->where('name', 'Courier')->exists())->toBeTrue();
});

test('store rejects duplicate contact categories', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.contact-categories.store'), ['name' => 'Customer'])
        ->assertSessionHasErrors('name');
});

test('verified users can update a contact category', function () {
    $user = User::factory()->create();
    $row = ContactCategory::factory()->create(['name' => 'Old']);

    $this->actingAs($user)
        ->put(route('settings.contact-categories.update', $row), ['name' => 'New'])
        ->assertRedirect(route('settings.contact-categories.index'));

    expect($row->fresh()->name)->toBe('New');
});

test('verified users can delete an unused contact category', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    $row = ContactCategory::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.contact-categories.destroy', $row))
        ->assertRedirect(route('settings.contact-categories.index'));

    expect(ContactCategory::query()->find($row->id))->toBeNull();
});

test('delete is blocked when contacts use the category', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    $row = ContactCategory::factory()->create();
    Contact::factory()->create(['contact_category_id' => $row->id]);

    $this->actingAs($user)
        ->delete(route('settings.contact-categories.destroy', $row))
        ->assertForbidden();

    expect(ContactCategory::query()->find($row->id))->not->toBeNull();
});
