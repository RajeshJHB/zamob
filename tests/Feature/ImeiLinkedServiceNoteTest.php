<?php

use App\Models\Contact;
use App\Models\Imei;
use App\Models\ServiceNote;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (! Schema::hasTable('imei')) {
        Schema::create('imei', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_in')->nullable();
            $table->string('cash_stock_type')->default('');
            $table->dateTime('date_updated')->nullable();
            $table->string('make')->default('');
            $table->string('model')->default('');
            $table->string('sn')->default('');
            $table->string('imei');
            $table->string('location')->default('');
            $table->string('type')->default('');
            $table->string('status')->default('');
            $table->text('notes')->nullable();
            $table->string('phonenumber')->default('');
            $table->string('ref')->default('');
            $table->string('staff')->default('');
            $table->string('item_code')->default('');
            $table->string('ourON')->default('');
            $table->string('salesON')->default('');
            $table->string('cost_excl')->default('');
            $table->integer('selling_price')->nullable();
        });
    }
});

function imeiStorePayload(array $overrides = []): array
{
    return array_merge([
        'imei_non_standard' => '1',
        'imei' => 'linkednote01',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => '',
        'type' => '',
        'status' => '',
        'notes' => '',
        'phonenumber' => '',
        'ref' => '',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '',
        'selling_price' => '',
    ], $overrides);
}

test('saving imei with close note closes open service note and appends imei to body', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'status' => ServiceNote::STATUS_OPEN,
        'heading' => 'Repair visit',
        'body' => 'Screen replacement booked.',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('imeis.store'), imeiStorePayload([
            'imei' => 'linkednote01',
            'linked_service_note_id' => $note->id,
            'close_linked_service_note' => '1',
        ]))
        ->assertRedirect();

    $note->refresh();

    expect($note->status)->toBe(ServiceNote::STATUS_CLOSED);
    expect($note->body)->toContain('Screen replacement booked.');
    expect($note->body)->toContain('IMEI: linkednote01');
    expect($note->staff)->toContain($user->email);
});

test('saving imei without close note checkbox leaves service note unchanged', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'status' => ServiceNote::STATUS_OPEN,
        'body' => 'Unchanged body.',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('imeis.store'), imeiStorePayload([
            'imei' => 'nochange01',
            'linked_service_note_id' => $note->id,
        ]))
        ->assertRedirect();

    $note->refresh();

    expect($note->status)->toBe(ServiceNote::STATUS_OPEN);
    expect($note->body)->toBe('Unchanged body.');
});

test('close note on update appends imei without reopening a closed note', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->closed()->create([
        'contact_id' => $contact->id,
        'body' => 'Already closed.',
        'created_by' => $user->id,
    ]);
    $imei = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'updateimei01',
        'cash_stock_type' => '',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => '',
        'type' => '',
        'status' => '',
        'notes' => '',
        'phonenumber' => '',
        'ref' => '',
        'staff' => '',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '',
        'selling_price' => null,
    ]);

    $this->actingAs($user)
        ->put(route('imeis.update', $imei), imeiStorePayload([
            'imei' => 'updateimei01',
            'linked_service_note_id' => $note->id,
            'close_linked_service_note' => '1',
        ]))
        ->assertRedirect(route('imeis.index', [
            'search' => 'updateimei01',
            'scope' => 'all',
            'date_scope' => 'all',
        ]));

    $note->refresh();

    expect($note->status)->toBe(ServiceNote::STATUS_CLOSED);
    expect($note->body)->toContain('IMEI: updateimei01');
});

test('scan out device changes in shop status to scanned out on update', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'status' => ServiceNote::STATUS_OPEN,
        'body' => 'Ready for collection.',
        'created_by' => $user->id,
    ]);
    \App\Models\ImeiStatus::query()->firstOrCreate(['status' => 'In Shop']);
    $imei = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'scanout01',
        'cash_stock_type' => '',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => '',
        'type' => '',
        'status' => 'In Shop',
        'notes' => '',
        'phonenumber' => '',
        'ref' => '',
        'staff' => '',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '',
        'selling_price' => null,
    ]);

    $this->actingAs($user)
        ->put(route('imeis.update', $imei), imeiStorePayload([
            'imei' => 'scanout01',
            'status' => 'In Shop',
            'linked_service_note_id' => $note->id,
            'scan_out_device' => '1',
        ]))
        ->assertRedirect();

    expect($imei->fresh()->status)->toBe(\App\Support\ImeiLinkedServiceNote::SCAN_OUT_STATUS);
    expect(\App\Models\ImeiStatus::query()->where('status', 'Scanned Out')->exists())->toBeTrue();
    expect($note->fresh()->status)->toBe(ServiceNote::STATUS_OPEN);
});

test('scan out device does not change status when imei is not in shop', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $note = ServiceNote::factory()->create([
        'contact_id' => $contact->id,
        'created_by' => $user->id,
    ]);
    \App\Models\ImeiStatus::query()->firstOrCreate(['status' => 'Sold']);
    $imei = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'scanout02',
        'cash_stock_type' => '',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => '',
        'type' => '',
        'status' => 'Sold',
        'notes' => '',
        'phonenumber' => '',
        'ref' => '',
        'staff' => '',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '',
        'selling_price' => null,
    ]);

    $this->actingAs($user)
        ->put(route('imeis.update', $imei), imeiStorePayload([
            'imei' => 'scanout02',
            'status' => 'Sold',
            'linked_service_note_id' => $note->id,
            'scan_out_device' => '1',
        ]))
        ->assertRedirect();

    expect($imei->fresh()->status)->toBe('Sold');
});
