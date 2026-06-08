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

test('lookup accepts non-standard imei and finds existing row by normalized key', function () {
    $user = User::factory()->create();
    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '12-34-56',
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
        ->getJson(route('imeis.lookup', ['imei' => '123456', 'non_standard' => '1']))
        ->assertSuccessful()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('exists', true)
        ->assertJsonPath('canonical_imei', '123456')
        ->assertJsonPath('non_standard', true);
});

test('lookup finds non-standard alphanumeric imei when separators differ', function () {
    $user = User::factory()->create();
    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'ab-12/x',
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
        ->getJson(route('imeis.lookup', ['imei' => 'ab 12-x', 'non_standard' => '1']))
        ->assertSuccessful()
        ->assertJsonPath('exists', true)
        ->assertJsonPath('canonical_imei', 'ab12x');
});

test('store accepts non-standard imei when imei_non_standard is 1', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => '98765',
            'date_in' => '',
        ]);

    $response->assertRedirect(imeiListUrlAfterSave('98765'));
    $this->assertDatabaseHas('imei', [
        'imei' => '98765',
    ]);
});

test('store accepts non-standard alphanumeric imei', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'ID#99-aa',
            'date_in' => '',
        ])
        ->assertRedirect(imeiListUrlAfterSave('ID#99aa'));

    $this->assertDatabaseHas('imei', [
        'imei' => 'ID#99aa',
    ]);
});

test('store rejects duplicate non-standard imei', function () {
    $user = User::factory()->create();
    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '11111',
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
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => '11111',
            'date_in' => '',
        ])
        ->assertSessionHasErrors('imei');
});
