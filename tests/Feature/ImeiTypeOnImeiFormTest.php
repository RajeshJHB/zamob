<?php

use App\Models\Imei;
use App\Models\ImeiType;
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

test('store rejects type that is not in imei_types', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'store-type-bad',
            'date_in' => '',
            'type' => 'NotInReferenceTable',
        ])
        ->assertSessionHasErrors('type');
});

test('store persists the chosen type string from imei_types', function () {
    $user = User::factory()->create();
    ImeiType::factory()->create(['type' => 'Retail']);

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'storetypeok',
            'date_in' => '',
            'type' => 'Retail',
        ])
        ->assertRedirect(route('imeis.create'));

    $this->assertDatabaseHas('imei', [
        'imei' => 'storetypeok',
        'type' => 'Retail',
    ]);
});

test('update allows unchanged legacy type when it is not in imei_types', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'upd-legacy-type',
        'stock_take_date' => '',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => '',
        'type' => 'LegacyFreeText',
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
            'type' => 'LegacyFreeText',
            'date_in' => '',
        ])
        ->assertRedirect(route('imeis.create'));

    expect(Imei::query()->find($row->id)?->type)->toBe('LegacyFreeText');
});

test('update rejects changing type to a value not in imei_types', function () {
    $user = User::factory()->create();
    ImeiType::factory()->create(['type' => 'Wholesale']);
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'upd-bad-type',
        'stock_take_date' => '',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => '',
        'type' => 'Wholesale',
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
            'type' => 'StillNotInTable',
            'date_in' => '',
        ])
        ->assertSessionHasErrors('type');
});

test('add imei form lists types from imei_types as options', function () {
    $user = User::factory()->create();
    ImeiType::factory()->create(['type' => 'Handset']);

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful()
        ->assertSee('name="type"', false)
        ->assertSee('Handset', false);
});
