<?php

use App\Models\Imei;
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

test('store rejects stock_take_date that is not in imei_sale_types', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'store-sale-type-bad',
            'date_in' => '',
            'stock_take_date' => 'NotInReferenceTable',
        ])
        ->assertSessionHasErrors('stock_take_date');
});

test('store persists the chosen sale type in stock_take_date', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'storesaleok',
            'date_in' => '',
            'stock_take_date' => 'Cash',
        ])
        ->assertRedirect(route('imeis.edit', Imei::query()->where('imei', 'storesaleok')->firstOrFail()));

    $this->assertDatabaseHas('imei', [
        'imei' => 'storesaleok',
        'stock_take_date' => 'Cash',
    ]);
});

test('add imei form lists sale types from imei_sale_types as options', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful()
        ->assertSee('name="stock_take_date"', false)
        ->assertSee('Voda_Sale', false)
        ->assertSee('Easy20wn', false);
});
