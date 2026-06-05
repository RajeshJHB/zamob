<?php

use App\Models\Contact;
use App\Models\NoteType;
use App\Models\ServiceNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('notes page shows search form and nav includes notes link', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertSuccessful()
        ->assertSee('Search notes', false)
        ->assertSee('All note types', false)
        ->assertSee('Selected types only', false)
        ->assertSee('Start date', false)
        ->assertSee('End date', false)
        ->assertSee('Showing open service notes', false)
        ->assertSee('Print', false)
        ->assertSee(route('notes.print'), false)
        ->assertSee('id="note_status"', false)
        ->assertSee('name="only_mine"', false)
        ->assertSee('onchange="this.form.submit()"', false)
        ->getContent();

    expect($html)->toContain(route('notes.index'))
        ->and($html)->toContain('>Notes</');
});

test('legacy notes search url redirects to notes index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notes.search', ['note_q' => 'router']))
        ->assertRedirect(route('notes.index', ['note_q' => 'router']));
});

test('contacts page shows search form without service notes link', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('contacts.index'))
        ->assertSuccessful()
        ->assertSee('Search contacts', false)
        ->assertSee('newest contacts', false)
        ->assertDontSee('Search all service notes', false);
});

test('legacy contacts search url redirects to contacts index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('contacts.search', ['q' => 'Smith']))
        ->assertRedirect(route('contacts.index', ['q' => 'Smith']));
});

test('legacy contacts notes search url redirects to notes index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('contacts.notes.search', ['note_q' => 'router']))
        ->assertRedirect(route('notes.index', ['note_q' => 'router']));
});

test('note type checkboxes list types in alphabetical order', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertSuccessful()
        ->getContent();

    $panelStart = strpos($html, 'id="notes-type-checkboxes"');
    expect($panelStart)->not->toBeFalse();

    $section = substr($html, $panelStart);
    $orderedNames = NoteType::query()->orderBy('name')->pluck('name')->all();
    $lastPosition = -1;

    foreach ($orderedNames as $name) {
        $position = strpos($section, '<span>'.$name.'</span>');
        expect($position)->not->toBeFalse()
            ->and($position)->toBeGreaterThan($lastPosition);
        $lastPosition = $position;
    }
});

test('notes page defaults to all note types selected', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('value="all"')
        ->and($html)->toContain('checked');
});

test('notes search filters by note type', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['first_name' => 'Pat', 'surname' => 'Lee']);
    $repairTypeId = (int) NoteType::query()->where('name', 'Repair')->value('id');
    $consultTypeId = (int) NoteType::query()->where('name', 'Consult')->value('id');

    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'note_type_id' => $repairTypeId,
        'heading' => 'Screen repair',
        'body' => 'Replaced cracked screen',
        'created_by' => $user->id,
    ]);
    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'note_type_id' => $consultTypeId,
        'heading' => 'Upgrade consult',
        'body' => 'Discussed plan options',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('notes.index', ['note_type_id' => $repairTypeId]))
        ->assertSuccessful()
        ->assertSee('Screen repair', false)
        ->assertDontSee('Upgrade consult', false)
        ->assertSee('Note type:', false)
        ->assertSee('Repair', false);
});

test('notes search filters by multiple note types', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['first_name' => 'Pat', 'surname' => 'Lee']);
    $repairTypeId = (int) NoteType::query()->where('name', 'Repair')->value('id');
    $consultTypeId = (int) NoteType::query()->where('name', 'Consult')->value('id');
    $upgradeTypeId = (int) NoteType::query()->where('name', 'Upgrade')->value('id');

    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'note_type_id' => $repairTypeId,
        'heading' => 'Screen repair',
        'body' => 'Replaced cracked screen',
        'created_by' => $user->id,
    ]);
    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'note_type_id' => $consultTypeId,
        'heading' => 'Upgrade consult',
        'body' => 'Discussed plan options',
        'created_by' => $user->id,
    ]);
    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'note_type_id' => $upgradeTypeId,
        'heading' => 'Plan upgrade',
        'body' => 'Moved to premium plan',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('notes.index', [
            'note_type_scope' => 'selected',
            'note_type_id' => [$consultTypeId, $upgradeTypeId],
            'note_status' => '',
        ]))
        ->assertSuccessful()
        ->assertSee('Upgrade consult', false)
        ->assertSee('Plan upgrade', false)
        ->assertDontSee('Screen repair', false)
        ->assertSee('Note types:', false)
        ->assertSee('Consult', false)
        ->assertSee('Upgrade', false);
});

test('notes search rejects selected note type scope without any types chosen', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('notes.index'))
        ->get(route('notes.index', [
            'note_type_scope' => 'selected',
            'note_status' => '',
        ]))
        ->assertRedirect(route('notes.index'))
        ->assertSessionHasErrors('note_type_id');
});

test('notes page defaults to open notes only', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'Open note',
        'body' => 'Still in progress',
        'status' => ServiceNote::STATUS_OPEN,
        'created_by' => $user->id,
    ]);
    ServiceNote::factory()->closed()->create([
        'contact_id' => $contact->id,
        'heading' => 'Closed note',
        'body' => 'Already completed',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('notes.index'))
        ->assertSuccessful()
        ->assertSee('Open note', false)
        ->assertDontSee('Closed note', false);

    $this->actingAs($user)
        ->get(route('notes.index', ['note_status' => ServiceNote::STATUS_CLOSED]))
        ->assertSuccessful()
        ->assertSee('Closed note', false)
        ->assertDontSee('Open note', false);

    $this->actingAs($user)
        ->get(route('notes.index', ['note_status' => '']))
        ->assertSuccessful()
        ->assertSee('Open note', false)
        ->assertSee('Closed note', false);
});

test('only my notes checkbox limits results to the logged in user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $contact = Contact::factory()->create();

    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'My open note',
        'status' => ServiceNote::STATUS_OPEN,
        'created_by' => $user->id,
    ]);
    ServiceNote::factory()->closed()->create([
        'contact_id' => $contact->id,
        'heading' => 'My closed note',
        'created_by' => $user->id,
    ]);
    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'Someone else open',
        'status' => ServiceNote::STATUS_OPEN,
        'created_by' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->get(route('notes.index', [
            'note_status' => ServiceNote::STATUS_OPEN,
            'only_mine' => '1',
        ]))
        ->assertSuccessful()
        ->assertSee('My open note', false)
        ->assertDontSee('My closed note', false)
        ->assertDontSee('Someone else open', false);
});

test('only my notes checkbox can be combined with all statuses', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $contact = Contact::factory()->create();

    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'My open note',
        'status' => ServiceNote::STATUS_OPEN,
        'created_by' => $user->id,
    ]);
    ServiceNote::factory()->closed()->create([
        'contact_id' => $contact->id,
        'heading' => 'My closed note',
        'created_by' => $user->id,
    ]);
    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'Someone else note',
        'created_by' => $otherUser->id,
    ]);

    $this->actingAs($user)
        ->get(route('notes.index', [
            'note_status' => '',
            'only_mine' => '1',
        ]))
        ->assertSuccessful()
        ->assertSee('My open note', false)
        ->assertSee('My closed note', false)
        ->assertDontSee('Someone else note', false);
});

test('notes search filters by start and end date', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();

    $januaryNote = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'January note',
        'body' => 'Early year visit',
        'created_by' => $user->id,
    ]);
    $januaryNote->forceFill(['noted_at' => '2024-01-15 10:00:00'])->saveQuietly();

    $marchNote = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'March note',
        'body' => 'Spring follow-up',
        'created_by' => $user->id,
    ]);
    $marchNote->forceFill(['noted_at' => '2024-03-20 14:30:00'])->saveQuietly();

    $this->actingAs($user)
        ->get(route('notes.index', [
            'start_date' => '2024-02-01',
            'end_date' => '2024-03-31',
            'note_status' => '',
        ]))
        ->assertSuccessful()
        ->assertSee('March note', false)
        ->assertDontSee('January note', false)
        ->assertSee('Dates:', false)
        ->assertSee('2024-02-01', false)
        ->assertSee('2024-03-31', false);
});

test('notes search rejects end date before start date', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('notes.index'))
        ->get(route('notes.index', [
            'start_date' => '2024-06-01',
            'end_date' => '2024-01-01',
            'note_status' => '',
        ]))
        ->assertRedirect(route('notes.index'))
        ->assertSessionHasErrors('end_date');
});

test('notes end date picker is constrained by selected start date', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notes.index', [
            'start_date' => '2024-06-01',
            'note_status' => '',
        ]))
        ->assertSuccessful()
        ->assertSee('id="notes_end_date"', false)
        ->assertSee('min="2024-06-01"', false);
});

test('notes browse lists service notes with start time and last time', function () {
    $user = User::factory()->create([
        'name' => 'Alex Logger',
        'email' => 'alex.logger@example.com',
    ]);
    $contact = Contact::factory()->create(['first_name' => 'Pat', 'surname' => 'Lee']);
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'Browse listed note',
        'body' => 'Visible in notes browse',
        'created_by' => $user->id,
    ]);
    $note->forceFill([
        'created_at' => Carbon::parse('2026-05-10 10:00:00'),
        'updated_at' => Carbon::parse('2026-05-10 15:30:00'),
    ])->saveQuietly();

    $this->actingAs($user)
        ->get(route('notes.index', ['note_status' => '']))
        ->assertSuccessful()
        ->assertSee('Browse listed note', false)
        ->assertSee('Alex Logger (alex.logger@example.com)', false)
        ->assertSee('Start Time:', false)
        ->assertSee('2026-05-10 10:00', false)
        ->assertSee('Last Time:', false)
        ->assertSee('2026-05-10 15:30', false);
});

test('notes print lists all filtered service notes in a table', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $repairTypeId = (int) NoteType::query()->where('name', 'Repair')->value('id');
    $consultTypeId = (int) NoteType::query()->where('name', 'Consult')->value('id');

    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'note_type_id' => $repairTypeId,
        'heading' => 'Print repair note',
        'body' => 'Repair body text',
        'status' => ServiceNote::STATUS_OPEN,
        'attachment_original_name' => 'photo.jpg',
        'created_by' => $user->id,
    ]);
    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'note_type_id' => $consultTypeId,
        'heading' => 'Print consult note',
        'body' => 'Consult body text',
        'status' => ServiceNote::STATUS_CLOSED,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('notes.print', [
            'note_type_scope' => 'selected',
            'note_type_id' => [$repairTypeId],
            'note_status' => '',
        ]))
        ->assertSuccessful()
        ->assertSee('Notes – All results (1 rows)', false)
        ->assertSee('Start Time', false)
        ->assertSee('Note Type', false)
        ->assertSee('Status', false)
        ->assertSee('Heading', false)
        ->assertSee('Attached file name', false)
        ->assertSee('Last Time', false)
        ->assertSee('Print repair note', false)
        ->assertSee('Repair body text', false)
        ->assertSee('photo.jpg', false)
        ->assertDontSee('Print consult note', false);
});

test('notes search lists and filters service notes', function () {
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

test('notes search matches contact name when note text does not contain the term', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create([
        'first_name' => 'Zandile',
        'surname' => 'Nkosi',
        'company_name' => '',
    ]);
    $other = Contact::factory()->create(['first_name' => 'Other', 'surname' => 'Person']);

    ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'heading' => 'Battery check',
        'body' => 'Checked voltage only',
        'created_by' => $user->id,
    ]);
    ServiceNote::factory()->create([
        'contact_id' => $other->id,
        'heading' => 'Unrelated visit',
        'body' => 'Routine',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('notes.index', [
            'note_q' => 'Nkosi',
            'note_status' => '',
        ]))
        ->assertSuccessful()
        ->assertSee('Battery check', false)
        ->assertSee('Zandile', false)
        ->assertDontSee('Unrelated visit', false);
});
