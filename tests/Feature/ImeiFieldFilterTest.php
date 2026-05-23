<?php

use App\Models\Imei;
use App\Models\ImeiLocation;
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
        ->assertSee('In Shop', false);
});

test('find imei index applies two field filters', function () {
    $user = User::factory()->create();

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '111111111111111',
        'stock_take_date' => '',
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
        'stock_take_date' => '',
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
        'stock_take_date' => '',
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
