<?php

use App\Models\Imei;
use App\Models\ImeiMake;
use App\Models\ImeiModel;
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

test('store rejects make that is not in imei_make', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'mkstorebad',
            'date_in' => '',
            'make' => 'UnknownMake',
        ])
        ->assertSessionHasErrors('make');
});

test('store rejects model that does not exist for the selected make', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Acme']);
    ImeiModel::factory()->create(['make' => 'OtherCo', 'model' => 'Z1', 'serial' => '']);

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'mkmodelbad',
            'date_in' => '',
            'make' => 'Acme',
            'model' => 'Z1',
        ])
        ->assertSessionHasErrors('model');
});

test('store rejects model when make is missing', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'mknomake',
            'date_in' => '',
            'model' => 'Anything',
        ])
        ->assertSessionHasErrors('model');
});

test('store persists make and model text from reference tables', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Vodacom']);
    ImeiModel::factory()->create(['make' => 'Vodacom', 'model' => 'Router', 'serial' => '']);

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'mkstoreok',
            'date_in' => '',
            'make' => 'Vodacom',
            'model' => 'Router',
        ])
        ->assertRedirect(route('imeis.create'));

    $this->assertDatabaseHas('imei', [
        'imei' => 'mkstoreok',
        'make' => 'Vodacom',
        'model' => 'Router',
    ]);
});

test('update allows unchanged legacy make and model when not in reference tables', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'mklegacy',
        'stock_take_date' => '',
        'make' => 'OldMake',
        'model' => 'OldModel',
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
        ->put(route('imeis.update', $row), [
            '_token' => csrf_token(),
            'make' => 'OldMake',
            'model' => 'OldModel',
            'date_in' => '',
        ])
        ->assertRedirect(route('imeis.create'));

    $fresh = Imei::query()->find($row->id);
    expect($fresh?->make)->toBe('OldMake');
    expect($fresh?->model)->toBe('OldModel');
});

test('update rejects changing make to a value not in imei_make', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Listed']);
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'mkupdmake',
        'stock_take_date' => '',
        'make' => 'Listed',
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
        ->put(route('imeis.update', $row), [
            '_token' => csrf_token(),
            'make' => 'NotListed',
            'date_in' => '',
        ])
        ->assertSessionHasErrors('make');
});

test('add imei form lists makes from imei_make', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Samsung']);

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful()
        ->assertSee('name="make"', false)
        ->assertSee('name="model"', false)
        ->assertSee('Samsung', false);
});

test('edit imei form lists models for the records make', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Samsung']);
    ImeiModel::factory()->create(['make' => 'Samsung', 'model' => 'Galaxy S', 'serial' => '']);
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'editsamsungx',
        'stock_take_date' => '',
        'make' => 'Samsung',
        'model' => 'Galaxy S',
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
        ->get(route('imeis.edit', $row))
        ->assertSuccessful()
        ->assertSee('Galaxy S', false);
});
