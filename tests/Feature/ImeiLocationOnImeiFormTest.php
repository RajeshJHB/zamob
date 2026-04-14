<?php

use App\Models\Imei;
use App\Models\ImeiLocation;
use App\Models\User;
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

test('store rejects location that is not in imei_locations', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'storelocbad',
            'date_in' => '',
            'location' => 'NotInReferenceTable',
        ])
        ->assertSessionHasErrors('location');
});

test('store persists the chosen location string from imei_locations', function () {
    $user = User::factory()->create();
    ImeiLocation::factory()->create(['location' => 'Warehouse A']);

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'storelocok',
            'date_in' => '',
            'location' => 'Warehouse A',
        ])
        ->assertRedirect(route('imeis.create'));

    $this->assertDatabaseHas('imei', [
        'imei' => 'storelocok',
        'location' => 'Warehouse A',
    ]);
});

test('update allows unchanged legacy location when it is not in imei_locations', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'upd-legacy-loc',
        'stock_take_date' => '',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => 'LegacyShelf',
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
        ->put(route('imeis.update', $row), [
            '_token' => csrf_token(),
            'location' => 'LegacyShelf',
            'date_in' => '',
        ])
        ->assertRedirect(route('imeis.create'));

    expect(Imei::query()->find($row->id)?->location)->toBe('LegacyShelf');
});

test('update rejects changing location to a value not in imei_locations', function () {
    $user = User::factory()->create();
    ImeiLocation::factory()->create(['location' => 'Front Desk']);
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'upd-bad-loc',
        'stock_take_date' => '',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => 'Front Desk',
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
        ->put(route('imeis.update', $row), [
            '_token' => csrf_token(),
            'location' => 'UnknownPlace',
            'date_in' => '',
        ])
        ->assertSessionHasErrors('location');
});

test('add imei form lists locations from imei_locations as options', function () {
    $user = User::factory()->create();
    ImeiLocation::factory()->create(['location' => 'Store Room']);

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful()
        ->assertSee('name="location"', false)
        ->assertSee('Store Room', false);
});
