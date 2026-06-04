<?php

use App\Models\Contact;
use App\Models\NoteType;
use App\Models\Role;
use App\Models\ServiceNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function contactUserWithRole4(): User
{
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(
        ['number' => 4],
        ['name' => 'Role 4'],
    );
    $user->roles()->syncWithoutDetaching([$role->id]);

    return $user;
}

function defaultNoteTypeId(): int
{
    return (int) NoteType::query()->orderBy('sort_order')->value('id');
}

function repairNoteTypeId(): int
{
    return (int) NoteType::query()->where('name', 'Repair')->value('id');
}

function serviceNotePayload(array $overrides = []): array
{
    return array_merge([
        'note_type_id' => defaultNoteTypeId(),
        'status' => ServiceNote::STATUS_OPEN,
        'heading' => 'Test heading',
        'body' => 'Test body content.',
    ], $overrides);
}

test('guests are redirected from contacts', function () {
    $this->get(route('contacts.index'))->assertRedirect(route('login'));
});

test('contact search across all fields finds matches', function () {
    $user = User::factory()->create();
    Contact::factory()->create(['telephone_1' => '0821112222', 'first_name' => 'Jane']);
    Contact::factory()->create(['telephone_1' => '0839998888']);

    $this->actingAs($user)
        ->get(route('contacts.index', ['q' => '082111']))
        ->assertRedirect(route('contacts.show', Contact::query()->where('telephone_1', '0821112222')->firstOrFail()));
});

test('multiple contact matches show selection table', function () {
    $user = User::factory()->create();
    Contact::factory()->create(['surname' => 'Smith', 'company_name' => 'Acme']);
    Contact::factory()->create(['surname' => 'Smith', 'company_name' => 'Beta']);

    $this->actingAs($user)
        ->get(route('contacts.index', ['q' => 'Smith']))
        ->assertSuccessful()
        ->assertSee('Acme', false)
        ->assertSee('Beta', false)
        ->assertSee('Select', false);
});

test('blank contact search lists all contacts newest first', function () {
    $user = User::factory()->create();
    $older = Contact::factory()->create(['first_name' => 'Older']);
    $older->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->save();
    $newer = Contact::factory()->create(['first_name' => 'Newer']);

    $html = $this->actingAs($user)
        ->get(route('contacts.index'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('newest contacts', false);
    expect($html)->toContain('200', false);
    expect($html)->toContain('Newer', false);
    expect($html)->toContain('Older', false);
    expect(strpos($html, 'Newer'))->toBeLessThan(strpos($html, 'Older'));
});

test('no contact match prompts create new contact', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('contacts.index', ['q' => 'not-in-db']))
        ->assertSuccessful()
        ->assertSee('No contact found', false)
        ->assertSee('Create new contact', false);
});

test('blank service note search lists all notes paginated', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    for ($i = 1; $i <= 21; $i++) {
        $note = ServiceNote::factory()->create([
            'contact_id' => $contact->id,
            'heading' => 'Note '.$i,
            'body' => 'Body for note '.$i,
            'created_by' => $user->id,
        ]);
        $note->forceFill(['noted_at' => now()->subMinutes($i)])->saveQuietly();
    }

    $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertSuccessful()
        ->assertSee('Showing open service notes', false)
        ->assertSee('Note 1', false)
        ->assertSee('Note 20', false)
        ->assertDontSee('Note 21', false);

    $this->actingAs($user)
        ->get(route('notes.index', ['page' => 2]))
        ->assertSuccessful()
        ->assertSee('Note 21', false)
        ->assertDontSee('Note 20', false);
});

test('service note search shows notes and contact links', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['first_name' => 'Pat', 'surname' => 'Lee']);
    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'Router install',
        'body' => 'Installed fibre router in lounge',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('notes.index', ['note_q' => 'fibre router']))
        ->assertSuccessful()
        ->assertSee('Router install', false)
        ->assertSee('Pat', false);
});

test('user can create contact and service note with attachment', function () {
    Storage::fake('attachments');
    $user = User::factory()->create();

    $contactResponse = $this->actingAs($user)
        ->post(route('contacts.store'), [
            'first_name' => 'Sam',
            'surname' => 'Wise',
            'telephone_1' => '0825551212',
        ]);

    $contact = Contact::query()->where('telephone_1', '0825551212')->firstOrFail();
    $contactResponse->assertRedirect(route('contacts.show', $contact));

    $file = UploadedFile::fake()->create('archive.zip', 100, 'application/zip');

    $noteResponse = $this->actingAs($user)
        ->post(route('contacts.service-notes.store', $contact), serviceNotePayload([
            'heading' => 'Initial visit',
            'body' => 'Customer onboarding complete.',
            'attachment' => $file,
        ]));

    $note = ServiceNote::query()->where('contact_id', $contact->id)->firstOrFail();
    $noteResponse->assertRedirect(route('contacts.show', ['contact' => $contact, 'note' => $note->id]));

    expect($note->noted_at)->not->toBeNull();
    expect($note->noted_at->toDateTimeString())->toBe($note->created_at->toDateTimeString());
    expect($note->note_number)->toBe(1);
    expect($note->formattedNoteNumber())->toBe('SN-1');
    expect($note->hasAttachment())->toBeTrue();
    expect($note->staff)->toContain($user->email);
    Storage::disk('attachments')->assertExists($note->attachment_path);
});

test('new service note form includes repair body template', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $this->actingAs($user)
        ->get(route('contacts.service-notes.create', $contact))
        ->assertSuccessful()
        ->assertSee('repairNoteBodyTemplate', false)
        ->assertSee('repairNoteHeadingTemplate', false)
        ->assertSee('repairNoteTypeId', false)
        ->assertSee('Repair Place: ', false)
        ->assertSee('Make:', false);
});

test('service note body is optional when heading is provided', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $this->actingAs($user)
        ->post(route('contacts.service-notes.store', $contact), serviceNotePayload([
            'heading' => 'Heading only visit',
            'body' => '',
        ]))
        ->assertRedirect();

    $note = ServiceNote::query()->where('heading', 'Heading only visit')->first();

    expect($note)->not->toBeNull();
    expect($note->body)->toBeNull();
});

test('service note requires note type', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $this->actingAs($user)
        ->post(route('contacts.service-notes.store', $contact), [
            'status' => ServiceNote::STATUS_OPEN,
            'heading' => 'Missing type',
            'body' => 'Body text.',
        ])
        ->assertSessionHasErrors('note_type_id');

    $this->actingAs($user)
        ->post(route('contacts.service-notes.store', $contact), [
            'note_type_id' => '',
            'status' => ServiceNote::STATUS_OPEN,
            'heading' => 'Missing type',
            'body' => 'Body text.',
        ])
        ->assertSessionHasErrors('note_type_id')
        ->assertSessionHasErrors(['note_type_id' => 'Please select a note type.']);
});

test('new service note form marks note type as required', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $this->actingAs($user)
        ->get(route('contacts.service-notes.create', $contact))
        ->assertSuccessful()
        ->assertSee('Note type', false)
        ->assertSee('aria-required="true"', false)
        ->assertSee('Select type…', false);
});

test('new service note recorded at matches creation timestamp in app timezone', function () {
    config(['app.timezone' => 'Africa/Johannesburg']);
    Carbon::setTestNow(Carbon::parse('2026-06-15 14:30:00', 'Africa/Johannesburg'));

    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $this->actingAs($user)
        ->post(route('contacts.service-notes.store', $contact), serviceNotePayload([
            'heading' => 'Timed note',
            'body' => 'Body text for timing test.',
        ]))
        ->assertRedirect();

    $note = ServiceNote::query()->where('heading', 'Timed note')->firstOrFail();

    expect($note->noted_at->format('Y-m-d H:i'))->toBe('2026-06-15 14:30');
    expect($note->noted_at->toDateTimeString())->toBe($note->created_at->toDateTimeString());
});

test('service note update does not change noted at', function () {
    Carbon::setTestNow('2026-05-10 10:00:00');
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'noted_at' => now(),
        'created_by' => $user->id,
    ]);
    $originalNotedAt = $note->noted_at->toDateTimeString();

    Carbon::setTestNow('2026-05-10 15:00:00');

    $this->actingAs($user)
        ->put(route('service-notes.update', $note), serviceNotePayload([
            'heading' => 'Updated heading',
            'body' => 'Updated body text here.',
        ]))
        ->assertRedirect();

    $note->refresh();
    expect($note->noted_at->toDateTimeString())->toBe($originalNotedAt);
    expect($note->heading)->toBe('Updated heading');
});

test('service note edit form shows start time and last time with note number', function () {
    Carbon::setTestNow('2026-05-10 10:00:00');
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'created_by' => $user->id,
    ]);
    $note->forceFill([
        'created_at' => Carbon::parse('2026-05-10 10:00:00'),
        'updated_at' => Carbon::parse('2026-05-10 15:30:00'),
    ])->saveQuietly();

    $this->actingAs($user)
        ->get(route('service-notes.edit', $note))
        ->assertSuccessful()
        ->assertSee($note->formattedNoteNumber(), false)
        ->assertSee('Start Time:', false)
        ->assertSee('2026-05-10 10:00', false)
        ->assertSee('Last Time:', false)
        ->assertSee('2026-05-10 15:30', false)
        ->assertDontSee('Recorded at', false);
});

test('contact page lists service notes with start time and last time', function () {
    Carbon::setTestNow('2026-05-10 10:00:00');
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'Listed note',
        'created_by' => $user->id,
    ]);
    $note->forceFill([
        'created_at' => Carbon::parse('2026-05-10 10:00:00'),
        'updated_at' => Carbon::parse('2026-05-10 15:30:00'),
    ])->saveQuietly();

    $this->actingAs($user)
        ->get(route('contacts.show', $contact))
        ->assertSuccessful()
        ->assertSee('Listed note', false)
        ->assertSee('Start Time:', false)
        ->assertSee('2026-05-10 10:00', false)
        ->assertSee('Last Time:', false)
        ->assertSee('2026-05-10 15:30', false);
});

test('user can delete contact created today but not yesterday without role 4', function () {
    $user = User::factory()->create();

    $todayContact = Contact::factory()->create();
    $todayContact->forceFill(['created_at' => now(), 'updated_at' => now()])->save();

    $oldContact = Contact::factory()->create();
    $oldContact->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->save();

    $this->actingAs($user)
        ->delete(route('contacts.destroy', $todayContact))
        ->assertRedirect(route('contacts.index'));

    $this->actingAs($user)
        ->delete(route('contacts.destroy', $oldContact))
        ->assertForbidden();

    expect(Contact::query()->find($oldContact->id))->not->toBeNull();
});

test('role 4 user can delete closed service note but not open', function () {
    $user = contactUserWithRole4();
    $contact = Contact::factory()->create();

    $openNote = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'created_by' => $user->id,
        'status' => ServiceNote::STATUS_OPEN,
    ]);

    $this->actingAs($user)
        ->delete(route('service-notes.destroy', $openNote))
        ->assertForbidden();

    $closedNote = ServiceNote::factory()->closed()->create([
        'contact_id' => $contact->id,
        'created_by' => $user->id,
    ]);
    $closedNote->forceFill(['created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)])->save();

    $this->actingAs($user)
        ->delete(route('service-notes.destroy', $closedNote))
        ->assertRedirect(route('contacts.show', $contact));

    expect(ServiceNote::query()->find($closedNote->id))->toBeNull();
});

test('role 4 user can delete old contact', function () {
    $user = contactUserWithRole4();
    $contact = Contact::factory()->create();
    $contact->forceFill(['created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3)])->save();

    $this->actingAs($user)
        ->delete(route('contacts.destroy', $contact))
        ->assertRedirect(route('contacts.index'));
});

test('user cannot delete service note without role 4 even when closed', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->closed()->create([
        'contact_id' => $contact->id,
        'created_by' => $user->id,
    ]);
    $note->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->save();

    $this->actingAs($user)
        ->delete(route('service-notes.destroy', $note))
        ->assertForbidden();
});

test('role 4 user can edit old service note', function () {
    $user = contactUserWithRole4();
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'Old',
        'body' => 'Old body',
        'created_by' => $user->id,
    ]);
    $note->forceFill(['created_at' => now()->subWeek(), 'updated_at' => now()->subWeek()])->save();

    $this->actingAs($user)
        ->put(route('service-notes.update', $note), serviceNotePayload([
            'heading' => 'Revised by role 4',
            'body' => 'Updated long after create.',
        ]))
        ->assertRedirect();

    expect($note->fresh()->heading)->toBe('Revised by role 4');
});

test('role 2 user can edit old service note', function () {
    $user = User::factory()->withoutRoles()->create();
    grantApplicationAccess($user, 2);
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'Old',
        'body' => 'Old body',
        'created_by' => $user->id,
    ]);
    $note->forceFill(['created_at' => now()->subWeek(), 'updated_at' => now()->subWeek()])->save();

    $this->actingAs($user)
        ->put(route('service-notes.update', $note), serviceNotePayload([
            'heading' => 'Revised by role 2',
            'body' => 'Updated long after create.',
        ]))
        ->assertRedirect();

    expect($note->fresh()->heading)->toBe('Revised by role 2');
});

test('role 3 user can edit old service note', function () {
    $role = Role::query()->firstOrCreate(
        ['number' => 3],
        ['name' => 'Role 3'],
    );
    $user = User::factory()->withoutRoles()->create();
    $user->roles()->sync([$role->id]);
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'Old',
        'body' => 'Old body',
        'created_by' => $user->id,
    ]);
    $note->forceFill(['created_at' => now()->subWeek(), 'updated_at' => now()->subWeek()])->save();

    $this->actingAs($user)
        ->put(route('service-notes.update', $note), serviceNotePayload([
            'heading' => 'Revised by role 3',
            'body' => 'Updated long after create.',
        ]))
        ->assertRedirect();

    expect($note->fresh()->heading)->toBe('Revised by role 3');
});

test('user without role 2 3 or 4 cannot edit old service note', function () {
    $role = Role::query()->firstOrCreate(
        ['number' => 5],
        ['name' => 'Role 5'],
    );
    $user = User::factory()->withoutRoles()->create();
    $user->roles()->sync([$role->id]);
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'Old',
        'body' => 'Old body',
        'created_by' => $user->id,
    ]);
    $note->forceFill(['created_at' => now()->subWeek(), 'updated_at' => now()->subWeek()])->save();

    $this->actingAs($user)
        ->get(route('service-notes.edit', $note))
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('service-notes.update', $note), serviceNotePayload([
            'heading' => 'Should not apply',
        ]))
        ->assertForbidden();
});

test('authenticated user can print repair service note with receipt layout', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'first_name' => 'Jane',
        'surname' => 'Doe',
    ]);
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'created_by' => $user->id,
        'note_type_id' => repairNoteTypeId(),
        'heading' => 'Repair',
        'body' => "IMEI: 358918502270111\nMake: Apple\nModel: iPhone 14\nProblem: Screen cracked\nPrice: R1500\nRepair Place: Blairgowrie",
    ]);

    $this->actingAs($user)
        ->get(route('service-notes.print', $note))
        ->assertSuccessful()
        ->assertSee('Vodacom by zaMobile', false)
        ->assertSee(route('imeis.receipt.logo'), false)
        ->assertSee('Reference Number:', false)
        ->assertSee($note->formattedNoteNumber(), false)
        ->assertSee('Jane', false)
        ->assertSee('Doe', false)
        ->assertSee('358918502270111', false)
        ->assertSee('Screen cracked', false)
        ->assertSee('www.myVodacom.co.za', false)
        ->assertSee('www.zaMobile.co.za', false)
        ->assertDontSee('Contact', false);
});

test('authenticated user can print non repair service note with standard layout', function () {
    $user = User::factory()->create();
    $note = ServiceNote::factory()->create(['created_by' => $user->id]);

    $this->actingAs($user)
        ->get(route('service-notes.print', $note))
        ->assertSuccessful()
        ->assertSee($note->formattedNoteNumber(), false)
        ->assertSee('Contact', false)
        ->assertDontSee('Vodacom by zaMobile', false);
});

test('note settings pages are available to authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.notes.index'))
        ->assertSuccessful()
        ->assertSee('Note Settings', false);

    $this->actingAs($user)
        ->get(route('settings.note-types.index'))
        ->assertSuccessful()
        ->assertSee('Consult', false)
        ->assertSee('Repair', false);
});
