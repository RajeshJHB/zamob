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

test('guests cannot fetch last imei for copy', function () {
    $this->getJson(route('imeis.last-for-copy'))->assertUnauthorized();
});

test('last for copy returns the most recently added record', function () {
    $user = User::factory()->create();

    Imei::query()->create([
        'date_in' => now()->subDay(),
        'date_updated' => now(),
        'imei' => '111111111111111',
        'stock_take_date' => '',
        'make' => 'OldMake',
        'model' => 'OldModel',
        'sn' => 'SN1',
        'location' => 'OldLoc',
        'type' => 'OldType',
        'status' => 'OldStatus',
        'notes' => 'Old notes',
        'phonenumber' => '111',
        'ref' => 'Old ref',
        'staff' => 'old@example.com',
        'item_code' => 'IC1',
        'ourON' => 'ON1',
        'salesON' => 'SON1',
        'cost_excl' => '10',
        'selling_price' => 100,
    ]);

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '222222222222222',
        'stock_take_date' => '',
        'make' => 'NewMake',
        'model' => 'NewModel',
        'sn' => 'SN2',
        'location' => 'NewLoc',
        'type' => 'NewType',
        'status' => 'NewStatus',
        'notes' => 'New notes',
        'phonenumber' => '222',
        'ref' => 'New ref',
        'staff' => 'new@example.com',
        'item_code' => 'IC2',
        'ourON' => 'ON2',
        'salesON' => 'SON2',
        'cost_excl' => '20',
        'selling_price' => 200,
    ]);

    $this->actingAs($user)
        ->getJson(route('imeis.last-for-copy'))
        ->assertSuccessful()
        ->assertJsonPath('record.imei', '222222222222222')
        ->assertJsonPath('record.make', 'NewMake')
        ->assertJsonPath('record.location', 'NewLoc')
        ->assertJsonPath('record.notes', 'New notes');
});

test('last for copy returns message when no records exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson(route('imeis.last-for-copy'))
        ->assertSuccessful()
        ->assertJsonPath('record', null)
        ->assertJsonPath('message', 'There are no IMEI records to copy yet.');
});

test('add imei form includes copy last button', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('imeis.create'))
        ->assertSuccessful()
        ->assertSee('id="imei-copy-last-btn"', false)
        ->assertSee('Copy Last', false);
});
