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
        ->assertRedirect(imeiListUrlAfterSave('mkstoreok'));

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
        'cash_stock_type' => '',
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
        ->assertRedirect(route('imeis.index', [
            'search' => 'mklegacy',
            'scope' => 'all',
            'date_scope' => 'all',
        ]));

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
        'cash_stock_type' => '',
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

test('add imei form includes model catalog with item code for auto fill', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Samsung']);
    ImeiModel::factory()->create([
        'make' => 'Samsung',
        'model' => 'Galaxy S24',
        'serial' => 'SN-ABC',
        'item_code' => 'IT-XYZ',
    ]);

    $html = $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('"item_code":"IT-XYZ"');
    expect($html)->toContain("label += ' (' + itemCode + ')'");
    expect($html)->not->toContain("label += ' (' + serial + ')'");
    expect($html)->toContain('applyModelCatalogFields');
    expect($html)->toContain('id="imei_final"');
    expect($html)->toContain('id="location"');
    expect(strpos($html, 'id="imei_final"'))->toBeLessThan(strpos($html, 'id="location"'));
    expect(strpos($html, 'id="make"'))->toBeLessThan(strpos($html, 'id="sn"'));
    expect(strpos($html, 'id="cash_stock_type"'))->toBeLessThan(strpos($html, 'id="date_in"'));
});

test('edit imei form lists models for the records make', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Samsung']);
    ImeiModel::factory()->create([
        'make' => 'Samsung',
        'model' => 'Galaxy S',
        'serial' => 'SN-OLD',
        'item_code' => 'SAM-GS',
    ]);
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'editsamsungx',
        'cash_stock_type' => '',
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
        ->assertSee('Galaxy S (SAM-GS)', false)
        ->assertDontSee('(SN-OLD)', false);
});

test('edit imei form lists model when make text differs only by whitespace', function () {
    $user = User::factory()->create();
    \Illuminate\Support\Facades\DB::table('imei_make')->insert([
        'make' => 'Oppo ',
        'date_added' => now(),
    ]);
    \Illuminate\Support\Facades\DB::table('imei_models')->insert([
        'make' => 'Oppo',
        'model' => 'A5 128GB 4G White',
        'serial' => '',
        'item_code' => '104077586',
        'date_added' => now(),
    ]);
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'oppowhitespace',
        'cash_stock_type' => '',
        'make' => 'Oppo ',
        'model' => 'A5 128GB 4G White',
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
        ->assertSee('A5 128GB 4G White', false);
});

test('store accepts model when make text differs only by whitespace', function () {
    $user = User::factory()->create();
    \Illuminate\Support\Facades\DB::table('imei_make')->insert([
        'make' => 'Oppo ',
        'date_added' => now(),
    ]);
    \Illuminate\Support\Facades\DB::table('imei_models')->insert([
        'make' => 'Oppo',
        'model' => 'A5 128GB 4G White',
        'serial' => '',
        'date_added' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'oppotrimtest',
            'date_in' => '',
            'make' => 'Oppo ',
            'model' => 'A5 128GB 4G White',
        ])
        ->assertRedirect(imeiListUrlAfterSave('oppotrimtest'));

    $this->assertDatabaseHas('imei', [
        'imei' => 'oppotrimtest',
        'make' => 'Oppo',
        'model' => 'A5 128GB 4G White',
    ]);
});

test('edit form selects make when stored value differs only by whitespace', function () {
    $user = User::factory()->create();
    \Illuminate\Support\Facades\DB::table('imei_make')->insert([
        'make' => 'Oppo ',
        'date_added' => now(),
    ]);
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'opposelecttrim',
        'cash_stock_type' => '',
        'make' => 'Oppo',
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

    $html = $this->actingAs($user)
        ->get(route('imeis.edit', $row))
        ->assertSuccessful()
        ->getContent();

    expect(preg_match('/<option[^>]*value="Oppo"[^>]*selected/i', $html))->toBe(1);
});

test('edit form loads when selling price is null', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'nullsellprice',
        'cash_stock_type' => '',
        'make' => 'Oppo',
        'model' => 'A5 128GB 4G White',
        'sn' => '',
        'location' => '',
        'type' => '',
        'status' => '',
        'notes' => 'Customer details from contact',
        'phonenumber' => '',
        'ref' => 'Deal details from note',
        'staff' => 'staff@example.com',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '2649',
        'selling_price' => null,
    ]);

    $this->actingAs($user)
        ->get(route('imeis.edit', $row))
        ->assertSuccessful()
        ->assertSee('nullsellprice', false)
        ->assertSee('Customer details from contact', false);
});

test('validation failure with empty selling price re-renders edit form', function () {
    $user = User::factory()->create();
    ImeiMake::factory()->create(['make' => 'Oppo']);
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'nullsellfail',
        'cash_stock_type' => '',
        'make' => 'Oppo',
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
        ->from(route('imeis.edit', $row))
        ->followingRedirects()
        ->put(route('imeis.update', $row), [
            '_token' => csrf_token(),
            'make' => 'NotARealMake',
            'model' => '',
            'selling_price' => '',
            'date_in' => '',
            'notes' => 'Browsed contact details',
            'ref' => 'Browsed note details',
        ])
        ->assertSuccessful()
        ->assertSee('Browsed contact details', false)
        ->assertSee('Browsed note details', false);
});
