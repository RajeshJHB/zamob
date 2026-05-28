<?php

use App\Models\Contact;
use App\Models\NoteType;
use App\Models\Role;
use App\Models\ServiceNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function serviceNoteLinkPayload(array $overrides = []): array
{
    return array_merge([
        'note_type_id' => (int) NoteType::query()->orderBy('sort_order')->value('id'),
        'status' => ServiceNote::STATUS_OPEN,
        'heading' => 'Follow-up heading',
        'body' => 'Follow-up body content.',
    ], $overrides);
}

function serviceNoteLinkUserWithRole4(): User
{
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(
        ['number' => 4],
        ['name' => 'Role 4'],
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

test('contact show displays related note button and link summary', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $related = ServiceNote::factory()->for($contact)->create([
        'heading' => 'Screen not working',
    ]);

    ServiceNote::factory()->for($contact)->create([
        'heading' => 'Called customer back',
        'primary_service_note_id' => $related->id,
    ]);

    $html = $this->actingAs($user)
        ->get(route('contacts.show', $contact))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('Related note', false);
    expect($html)->toContain('1 note link here', false);
    expect($html)->toContain('Called customer back', false);
    expect($html)->toContain('Related service note:', false);
    expect($html)->toContain('Screen not working', false);
});

test('user can create a service note linked to any other note for the contact', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $target = ServiceNote::factory()->for($contact)->create([
        'heading' => 'Main repair issue',
    ]);

    $this->actingAs($user)
        ->post(route('contacts.service-notes.store', $contact), serviceNoteLinkPayload([
            'primary_service_note_id' => $target->id,
            'heading' => 'Parts ordered',
        ]))
        ->assertRedirect();

    $linked = ServiceNote::query()->where('heading', 'Parts ordered')->first();

    expect($linked)->not->toBeNull();
    expect($linked->primary_service_note_id)->toBe($target->id);
});

test('user can link a note to another note that is itself linked', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $root = ServiceNote::factory()->for($contact)->create(['heading' => 'Root']);
    $child = ServiceNote::factory()->for($contact)->create([
        'heading' => 'Child',
        'primary_service_note_id' => $root->id,
    ]);

    $this->actingAs($user)
        ->post(route('contacts.service-notes.store', $contact), serviceNoteLinkPayload([
            'primary_service_note_id' => $child->id,
            'heading' => 'Links to child',
        ]))
        ->assertRedirect();

    expect(ServiceNote::query()->where('heading', 'Links to child')->value('primary_service_note_id'))
        ->toBe($child->id);
});

test('related note create form lists all contact notes in dropdown', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $first = ServiceNote::factory()->for($contact)->create(['heading' => 'First note']);
    ServiceNote::factory()->for($contact)->create([
        'heading' => 'Second note',
        'primary_service_note_id' => $first->id,
    ]);

    $this->actingAs($user)
        ->get(route('contacts.service-notes.create', $contact))
        ->assertSuccessful()
        ->assertSee('Related service note (optional)', false)
        ->assertSee('First note', false)
        ->assertSee('Second note', false);
});

test('related note create form preselects note from query string', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $target = ServiceNote::factory()->for($contact)->create([
        'heading' => 'Battery fault',
    ]);

    $this->actingAs($user)
        ->get(route('contacts.service-notes.create', [
            'contact' => $contact,
            'primary_service_note_id' => $target->id,
        ]))
        ->assertSuccessful()
        ->assertSee('New related service note', false)
        ->assertSee('Battery fault', false)
        ->assertSee('value="'.$target->id.'" selected', false);
});

test('cannot link a service note to itself', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $note = ServiceNote::factory()->for($contact)->create();

    $this->actingAs($user)
        ->put(route('service-notes.update', $note), serviceNoteLinkPayload([
            'primary_service_note_id' => $note->id,
            'heading' => $note->heading,
            'body' => $note->body,
        ]))
        ->assertSessionHasErrors('primary_service_note_id');
});

test('user can change related note on edit', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $targetA = ServiceNote::factory()->for($contact)->create(['heading' => 'Target A']);
    $targetB = ServiceNote::factory()->for($contact)->create(['heading' => 'Target B']);
    $note = ServiceNote::factory()->for($contact)->create([
        'primary_service_note_id' => $targetA->id,
    ]);

    $this->actingAs($user)
        ->put(route('service-notes.update', $note), serviceNoteLinkPayload([
            'primary_service_note_id' => $targetB->id,
            'heading' => $note->heading,
            'body' => $note->body,
        ]))
        ->assertRedirect();

    expect($note->fresh()->primary_service_note_id)->toBe($targetB->id);
});

test('deleting related note clears links on notes that pointed to it', function () {
    $user = serviceNoteLinkUserWithRole4();
    $contact = Contact::factory()->create();

    $target = ServiceNote::factory()->for($contact)->closed()->create();
    $linked = ServiceNote::factory()->for($contact)->create([
        'primary_service_note_id' => $target->id,
    ]);

    $this->actingAs($user)
        ->delete(route('service-notes.destroy', $target))
        ->assertRedirect();

    expect($linked->fresh()->primary_service_note_id)->toBeNull();
});
