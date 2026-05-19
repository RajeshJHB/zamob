<?php

use App\Models\Imei;
use App\Models\User;
use App\Support\ImeiStaffAudit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('imei', function (Blueprint $table) {
        $table->id();
        $table->dateTime('date_in')->nullable();
        $table->string('stock_take_date')->default('');
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
});

test('appendEmail adds email to empty string', function () {
    expect(ImeiStaffAudit::appendEmail('', 'alice@example.com'))
        ->toBe('alice@example.com');
});

test('appendEmail does not duplicate same email case insensitively', function () {
    expect(ImeiStaffAudit::appendEmail('alice@example.com', 'Alice@Example.com'))
        ->toBe('alice@example.com');
});

test('appendEmail appends second distinct email', function () {
    expect(ImeiStaffAudit::appendEmail('alice@example.com', 'bob@example.com'))
        ->toBe('alice@example.com, bob@example.com');
});

test('store sets staff to the authenticated user email', function () {
    $user = User::factory()->create(['email' => 'creator@example.com']);

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'staffcreate',
            'date_in' => '',
        ])
        ->assertRedirect(route('imeis.edit', Imei::query()->where('imei', 'staffcreate')->firstOrFail()));

    $this->assertDatabaseHas('imei', [
        'imei' => 'staffcreate',
        'staff' => 'creator@example.com',
    ]);
});

test('store ignores staff value submitted from the client', function () {
    $user = User::factory()->create(['email' => 'creator@example.com']);

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'staffignore',
            'date_in' => '',
            'staff' => 'forged@example.com',
        ])
        ->assertRedirect(route('imeis.edit', Imei::query()->where('imei', 'staffignore')->firstOrFail()));

    $this->assertDatabaseHas('imei', [
        'imei' => 'staffignore',
        'staff' => 'creator@example.com',
    ]);
});

test('update appends editor email without duplicating creator on second save', function () {
    $creator = User::factory()->create(['email' => 'creator@example.com']);
    $editor = User::factory()->create(['email' => 'editor@example.com']);

    $this->actingAs($creator)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'staffupdate',
            'date_in' => '',
        ]);

    $imei = Imei::query()->where('imei', 'staffupdate')->firstOrFail();

    $this->actingAs($editor)
        ->put(route('imeis.update', $imei), [
            '_token' => csrf_token(),
            'date_in' => '',
            'notes' => 'updated',
        ])
        ->assertRedirect(route('imeis.edit', $imei));

    expect($imei->fresh()->staff)->toBe('creator@example.com, editor@example.com');

    $this->actingAs($editor)
        ->put(route('imeis.update', $imei), [
            '_token' => csrf_token(),
            'date_in' => '',
            'notes' => 'updated again',
        ])
        ->assertRedirect(route('imeis.edit', $imei));

    expect($imei->fresh()->staff)->toBe('creator@example.com, editor@example.com');
});

test('update preserves legacy staff text and appends editor email once', function () {
    $user = User::factory()->create(['email' => 'editor@example.com']);
    $imei = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'legacystaff',
        'stock_take_date' => '',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => '',
        'type' => '',
        'status' => '',
        'notes' => '',
        'phonenumber' => '',
        'ref' => '',
        'staff' => 'Legacy name',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '',
        'selling_price' => null,
    ]);

    $this->actingAs($user)
        ->put(route('imeis.update', $imei), [
            '_token' => csrf_token(),
            'date_in' => '',
        ])
        ->assertRedirect(route('imeis.edit', $imei));

    expect($imei->fresh()->staff)->toBe('Legacy name, editor@example.com');

    $this->actingAs($user)
        ->put(route('imeis.update', $imei), [
            '_token' => csrf_token(),
            'date_in' => '',
        ]);

    expect($imei->fresh()->staff)->toBe('Legacy name, editor@example.com');
});

test('add imei form shows staff as read only without a name attribute', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('id="staff"')
        ->and($html)->toContain('readonly')
        ->and($html)->toContain('filled in automatically')
        ->and($html)->not->toContain('name="staff"');
});
