<?php

use App\Models\Contact;
use App\Models\NoteType;
use App\Models\ServiceNote;
use App\Models\User;
use App\Support\ContactImeiCustomerDetails;
use App\Support\ServiceNoteDealDetails;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests cannot browse contacts for imei form', function () {
    $this->get(route('imeis.contacts.browse'))->assertRedirect(route('login'));
});

test('imei contacts browse returns contacts in alphabetical order with customer details text', function () {
    $user = User::factory()->create();

    Contact::factory()->create([
        'company_name' => 'Zulu Corp',
        'first_name' => 'Ann',
        'surname' => 'Zulu',
        'telephone_1' => '0821111111',
    ]);
    Contact::factory()->create([
        'company_name' => 'Alpha Ltd',
        'first_name' => 'Bob',
        'surname' => 'Alpha',
        'telephone_1' => '0822222222',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('imeis.contacts.browse'))
        ->assertSuccessful();

    $contacts = $response->json('contacts');
    expect($contacts)->toHaveCount(2);
    expect($contacts[0]['company_name'])->toBe('Alpha Ltd');
    expect($contacts[1]['company_name'])->toBe('Zulu Corp');
    expect($contacts[0]['customer_details'])->toBe(ContactImeiCustomerDetails::format(
        Contact::query()->where('company_name', 'Alpha Ltd')->firstOrFail()
    ));
});

test('imei contacts browse filters by search query', function () {
    $user = User::factory()->create();

    Contact::factory()->create([
        'company_name' => 'Match Corp',
        'first_name' => 'Pat',
        'surname' => 'Lee',
        'telephone_1' => '0821112222',
    ]);
    Contact::factory()->create([
        'company_name' => 'Other Corp',
        'first_name' => 'Sam',
        'surname' => 'Other',
        'telephone_1' => '0839998888',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('imeis.contacts.browse', ['q' => '082111']))
        ->assertSuccessful();

    $contacts = $response->json('contacts');
    expect($contacts)->toHaveCount(1);
    expect($contacts[0]['company_name'])->toBe('Match Corp');
});

test('add and edit imei form includes browse contacts button', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful()
        ->assertSee('imei-browse-contacts-btn', false)
        ->assertSee('imei-browse-deal-notes-btn', false)
        ->assertSee('Browse notes', false)
        ->assertSee('Browse', false)
        ->assertSee('id="imei-contact-browse-search"', false)
        ->assertSee('id="close_linked_service_note"', false)
        ->assertSee('Close Note', false)
        ->assertSee('id="scan_out_device"', false)
        ->assertSee('Scan out Device', false);
});

test('imei service notes browse returns notes for contact newest first', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $noteTypeId = (int) NoteType::query()->orderBy('sort_order')->value('id');

    $older = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'note_type_id' => $noteTypeId,
        'heading' => 'Older visit',
        'body' => 'Older body text.',
        'noted_at' => now()->subDay(),
        'created_by' => $user->id,
    ]);
    $newer = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'note_type_id' => $noteTypeId,
        'heading' => 'Latest visit',
        'body' => 'Latest body text.',
        'noted_at' => now(),
        'created_by' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('imeis.contacts.service-notes.browse', $contact))
        ->assertSuccessful();

    $notes = $response->json('notes');
    expect($notes)->toHaveCount(2);
    expect($notes[0]['id'])->toBe($newer->id);
    expect($notes[1]['id'])->toBe($older->id);
    expect($notes[0]['deal_details_text'])->toBe(ServiceNoteDealDetails::format($newer));
});
