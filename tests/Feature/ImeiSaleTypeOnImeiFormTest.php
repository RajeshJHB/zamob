<?php

use App\Models\ImeiSaleType;
use App\Models\User;
use App\Support\ImeiNewRecordDefaults;
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

test('store rejects cash_stock_type that is not in imei_sale_types', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'store-sale-type-bad',
            'date_in' => '',
            'cash_stock_type' => 'NotInReferenceTable',
        ])
        ->assertSessionHasErrors('cash_stock_type');
});

test('store persists the chosen sale type in cash_stock_type', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'storesaleok',
            'date_in' => '',
            'cash_stock_type' => 'Cash',
        ])
        ->assertRedirect(imeiListUrlAfterSave('storesaleok'));

    $this->assertDatabaseHas('imei', [
        'imei' => 'storesaleok',
        'cash_stock_type' => 'Cash',
    ]);
});

test('add imei form lists sale types from imei_sale_types as options', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful()
        ->assertSee('name="cash_stock_type"', false)
        ->assertSee('Voda_Sale', false)
        ->assertSee('Easy20wn', false);
});

test('add imei form defaults sale type to none', function () {
    $user = User::factory()->create();
    ImeiSaleType::query()->firstOrCreate(['sale_type' => 'None']);

    $html = $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('value="'.ImeiNewRecordDefaults::SALE_TYPE.'" selected');
});

test('store defaults cash_stock_type to none when omitted', function () {
    $user = User::factory()->create();
    ImeiSaleType::query()->firstOrCreate(['sale_type' => 'None']);

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'defaultsaletype',
            'date_in' => '',
        ])
        ->assertRedirect(imeiListUrlAfterSave('defaultsaletype'));

    $this->assertDatabaseHas('imei', [
        'imei' => 'defaultsaletype',
        'cash_stock_type' => 'None',
    ]);
});
