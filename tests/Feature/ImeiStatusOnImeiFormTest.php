<?php

use App\Models\Imei;
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

test('store rejects status that is not in imei_statuses', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'storestatbad',
            'date_in' => '',
            'status' => 'NotInReferenceTable',
        ])
        ->assertSessionHasErrors('status');
});

test('store persists the chosen status string from imei_statuses', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'In Stock']);

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            '_token' => csrf_token(),
            'imei_non_standard' => '1',
            'imei' => 'storestatok',
            'date_in' => '',
            'status' => 'In Stock',
        ])
        ->assertRedirect(route('imeis.create'));

    $this->assertDatabaseHas('imei', [
        'imei' => 'storestatok',
        'status' => 'In Stock',
    ]);
});

test('update allows unchanged legacy status when it is not in imei_statuses', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'upd-legacy-st',
        'stock_take_date' => '',
        'make' => '',
        'model' => '',
        'sn' => '',
        'location' => '',
        'type' => '',
        'status' => 'LegacyStatusText',
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
            'status' => 'LegacyStatusText',
            'date_in' => '',
        ])
        ->assertRedirect(route('imeis.create'));

    expect(Imei::query()->find($row->id)?->status)->toBe('LegacyStatusText');
});

test('update rejects changing status to a value not in imei_statuses', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'Sold']);
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'upd-bad-st',
        'stock_take_date' => '',
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
        ->put(route('imeis.update', $row), [
            '_token' => csrf_token(),
            'status' => 'StillNotInTable',
            'date_in' => '',
        ])
        ->assertSessionHasErrors('status');
});

test('add imei form lists statuses from imei_statuses as options', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'Returned']);

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful()
        ->assertSee('name="status"', false)
        ->assertSee('Returned', false);
});
