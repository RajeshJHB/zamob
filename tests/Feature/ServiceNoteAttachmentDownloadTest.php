<?php

use App\Models\ServiceNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('authenticated user can preview supported attachment inline', function () {
    Storage::fake('attachments');

    $user = User::factory()->create();
    $note = ServiceNote::factory()->create([
        'attachment_path' => 'service-notes/1/note.pdf',
        'attachment_original_name' => 'note.pdf',
        'attachment_mime' => 'application/pdf',
        'attachment_size' => 128,
    ]);

    Storage::disk('attachments')->put($note->attachment_path, 'pdf-content');

    $response = $this->actingAs($user)
        ->get(route('service-notes.attachment.preview', $note));

    $response->assertSuccessful();
    expect($response->headers->get('content-disposition'))->toContain('inline');
});

test('preview returns not found for unsupported attachment types', function () {
    Storage::fake('attachments');

    $user = User::factory()->create();
    $note = ServiceNote::factory()->create([
        'attachment_path' => 'service-notes/1/archive.zip',
        'attachment_original_name' => 'archive.zip',
        'attachment_mime' => 'application/zip',
        'attachment_size' => 128,
    ]);

    Storage::disk('attachments')->put($note->attachment_path, 'zip-content');

    $this->actingAs($user)
        ->get(route('service-notes.attachment.preview', $note))
        ->assertNotFound();
});

test('attachment download keeps attachment disposition', function () {
    Storage::fake('attachments');

    $user = User::factory()->create();
    $note = ServiceNote::factory()->create([
        'attachment_path' => 'service-notes/1/note.pdf',
        'attachment_original_name' => 'note.pdf',
        'attachment_mime' => 'application/pdf',
        'attachment_size' => 128,
    ]);

    Storage::disk('attachments')->put($note->attachment_path, 'pdf-content');

    $response = $this->actingAs($user)
        ->get(route('service-notes.attachment', $note));

    $response->assertSuccessful();
    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

test('guest cannot preview or download service note attachments', function () {
    Storage::fake('attachments');

    $note = ServiceNote::factory()->create([
        'attachment_path' => 'service-notes/1/note.pdf',
        'attachment_original_name' => 'note.pdf',
        'attachment_mime' => 'application/pdf',
        'attachment_size' => 128,
    ]);

    Storage::disk('attachments')->put($note->attachment_path, 'pdf-content');

    $this->get(route('service-notes.attachment.preview', $note))->assertRedirect(route('login'));
    $this->get(route('service-notes.attachment', $note))->assertRedirect(route('login'));
});

test('contact page shows preview action for supported attachments', function () {
    Storage::fake('attachments');

    $user = User::factory()->create();
    $note = ServiceNote::factory()->create([
        'attachment_path' => 'service-notes/1/photo.jpg',
        'attachment_original_name' => 'photo.jpg',
        'attachment_mime' => 'image/jpeg',
        'attachment_size' => 128,
    ]);

    Storage::disk('attachments')->put($note->attachment_path, 'image-content');

    $this->actingAs($user)
        ->get(route('contacts.show', $note->contact_id))
        ->assertSuccessful()
        ->assertSee('data-service-note-preview', false)
        ->assertSee('Preview', false)
        ->assertSee('Download', false);
});
