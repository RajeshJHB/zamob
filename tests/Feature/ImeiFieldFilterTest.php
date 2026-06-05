<?php

use App\Models\AppSetting;
use App\Models\Imei;
use App\Models\ImeiLocation;
use App\Models\ImeiSaleType;
use App\Models\ImeiStatus;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
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
});

test('filter page retains field filter values when opened from query string', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'In Shop']);
    ImeiLocation::factory()->create(['location' => 'Shop A']);

    $this->actingAs($user)
        ->get(route('imeis.filter', [
            'field_filter_1' => 'status',
            'field_value_1' => 'In Shop',
            'field_filter_2' => 'location',
            'field_value_2' => 'Shop A',
        ]))
        ->assertSuccessful()
        ->assertSee('<option value="In Shop" selected>', false)
        ->assertSee('<option value="Shop A" selected>', false);
});

test('filter link from index retains field filter values', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'In Shop']);

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'field_filter_1' => 'status',
            'field_value_1' => 'In Shop',
        ]))
        ->assertSuccessful()
        ->assertSee('imeis/filter?field_filter_1=status', false)
        ->assertSee('field_value_1=In%20Shop', false);

    $this->actingAs($user)
        ->get(route('imeis.filter', [
            'field_filter_1' => 'status',
            'field_value_1' => 'In Shop',
        ]))
        ->assertSuccessful()
        ->assertSee('<option value="In Shop" selected>', false);
});

test('filter page includes field filter controls with no filter default', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'In Shop']);
    ImeiLocation::factory()->create(['location' => 'zaMobile Blairgowrie']);

    $this->actingAs($user)
        ->get(route('imeis.filter'))
        ->assertSuccessful()
        ->assertSee('Filter by field', false)
        ->assertSee('No filter', false)
        ->assertSee('field_filter_1', false)
        ->assertSee('field_filter_2', false)
        ->assertSee('field_filter_3', false)
        ->assertSee('Sale Type', false)
        ->assertSee('Exclude (not equal', false)
        ->assertSee('In Shop', false);
});

test('find imei index applies three field filters including exclude sale type', function () {
    $user = User::factory()->create();

    ImeiSaleType::query()->firstOrCreate(['sale_type' => 'Cash']);
    ImeiSaleType::query()->firstOrCreate(['sale_type' => 'Finance']);

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '111111111111111',
        'cash_stock_type' => 'Cash',
        'make' => 'Apple',
        'model' => 'A1',
        'sn' => '',
        'location' => 'Shop A',
        'type' => 'Cash',
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

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '222222222222222',
        'cash_stock_type' => 'Finance',
        'make' => 'Samsung',
        'model' => 'S1',
        'sn' => '',
        'location' => 'Shop A',
        'type' => 'Cash',
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
        ->get(route('imeis.index', [
            'field_filter_1' => 'status',
            'field_value_1' => 'In Shop',
            'field_filter_2' => 'location',
            'field_value_2' => 'Shop A',
            'field_filter_3' => 'sale_type',
            'field_value_3' => 'Cash',
            'field_not_3' => '1',
        ]))
        ->assertSuccessful()
        ->assertSee('222222222222222', false)
        ->assertDontSee('111111111111111', false);
});

test('find imei index applies two field filters', function () {
    $user = User::factory()->create();

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '111111111111111',
        'cash_stock_type' => '',
        'make' => 'Apple',
        'model' => 'A1',
        'sn' => '',
        'location' => 'Shop A',
        'type' => 'Cash',
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

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '222222222222222',
        'cash_stock_type' => '',
        'make' => 'Samsung',
        'model' => 'S1',
        'sn' => '',
        'location' => 'Shop A',
        'type' => 'Cash',
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

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '333333333333333',
        'cash_stock_type' => '',
        'make' => 'Apple',
        'model' => 'A2',
        'sn' => '',
        'location' => 'Shop B',
        'type' => 'Cash',
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
        ->get(route('imeis.index', [
            'field_filter_1' => 'status',
            'field_value_1' => 'In Shop',
            'field_filter_2' => 'location',
            'field_value_2' => 'Shop A',
        ]))
        ->assertSuccessful()
        ->assertSee('111111111111111', false)
        ->assertDontSee('222222222222222', false)
        ->assertDontSee('333333333333333', false);
});

test('search from filter with none profile and all columns keeps field filters without browse cap', function () {
    AppSetting::setBrowseListLimit(50);
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'In Shop']);

    for ($i = 0; $i < 60; $i++) {
        Imei::query()->create([
            'date_in' => now(),
            'date_updated' => now(),
            'imei' => 'BROWSE'.$i,
            'cash_stock_type' => '',
            'make' => 'Make',
            'model' => 'Model',
            'sn' => '',
            'location' => '',
            'type' => '',
            'status' => 'Available',
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
    }

    for ($i = 0; $i < 5; $i++) {
        Imei::query()->create([
            'date_in' => now(),
            'date_updated' => now(),
            'imei' => 'INSHOP'.$i,
            'cash_stock_type' => '',
            'make' => 'Make',
            'model' => 'Model',
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
    }

    $this->actingAs($user)
        ->get(route('imeis.profile.clear'))
        ->assertRedirect();

    $this->actingAs($user)
        ->followingRedirects()
        ->get(route('imeis.index', [
            'from_filter' => '1',
            'profile_id' => '',
            'scope' => 'all',
            'date_scope' => 'all',
            'field_filter_1' => 'status',
            'field_value_1' => 'In Shop',
        ]))
        ->assertSuccessful()
        ->assertViewHas('imeis', fn ($paginator) => $paginator->total() === 5)
        ->assertSee('INSHOP0', false)
        ->assertDontSee('BROWSE0', false);
});

test('imei index hides cost incl total on unfiltered browse', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('imeis.index'))
        ->assertSuccessful()
        ->assertDontSee('id="imei-filtered-cost-incl-total"', false);
});

test('imei index shows cost incl total for all matching filtered records', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'In Shop']);

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '111111111111111',
        'cash_stock_type' => '',
        'make' => 'Apple',
        'model' => 'A1',
        'sn' => '',
        'location' => '',
        'type' => 'Cash',
        'status' => 'In Shop',
        'notes' => '',
        'phonenumber' => '',
        'ref' => '',
        'staff' => '',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '100',
        'selling_price' => null,
    ]);

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '222222222222222',
        'cash_stock_type' => '',
        'make' => 'Apple',
        'model' => 'A2',
        'sn' => '',
        'location' => '',
        'type' => 'Cash',
        'status' => 'In Shop',
        'notes' => '',
        'phonenumber' => '',
        'ref' => '',
        'staff' => '',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '200',
        'selling_price' => null,
    ]);

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '333333333333333',
        'cash_stock_type' => '',
        'make' => 'Apple',
        'model' => 'A3',
        'sn' => '',
        'location' => '',
        'type' => 'Cash',
        'status' => 'Sold',
        'notes' => '',
        'phonenumber' => '',
        'ref' => '',
        'staff' => '',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '999',
        'selling_price' => null,
    ]);

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'field_filter_1' => 'status',
            'field_value_1' => 'In Shop',
        ]))
        ->assertSuccessful()
        ->assertSee('id="imei-filtered-cost-incl-total"', false)
        ->assertSee('>345<', false);
});

test('imei index default sort uses date updated then date in newest first', function () {
    $user = User::factory()->create();
    $sharedUpdated = now()->subDay();

    Imei::query()->create([
        'date_in' => now()->subDays(3),
        'date_updated' => $sharedUpdated,
        'imei' => 'OLDER-DATE-IN',
        'cash_stock_type' => '',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => '',
        'type' => '',
        'status' => 'SortTest',
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

    Imei::query()->create([
        'date_in' => now()->subDay(),
        'date_updated' => $sharedUpdated,
        'imei' => 'NEWER-DATE-IN',
        'cash_stock_type' => '',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => '',
        'type' => '',
        'status' => 'SortTest',
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
        ->get(route('imeis.index', [
            'field_filter_1' => 'status',
            'field_value_1' => 'SortTest',
        ]))
        ->assertSuccessful()
        ->getContent();

    expect(strpos($html, 'NEWER-DATE-IN'))->toBeLessThan(strpos($html, 'OLDER-DATE-IN'));
});
