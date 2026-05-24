<?php

use App\Models\Imei;
use App\Models\User;
use App\Support\CashDevicesTable;
use App\Support\ImeiCashDeviceType;
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

test('guests are redirected from dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('dashboard lists cash devices with view link to imei record', function () {
    $user = User::factory()->create();

    $cashDevice = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270111',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'iPhone 14',
        'sn' => '',
        'location' => '',
        'type' => ImeiCashDeviceType::TYPE,
        'status' => ImeiCashDeviceType::STATUS,
        'notes' => '',
        'phonenumber' => '',
        'ref' => '128GB Black, excellent condition',
        'staff' => '',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '',
        'selling_price' => 9999,
    ]);

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270222',
        'stock_take_date' => '',
        'make' => 'Samsung',
        'model' => 'Galaxy',
        'sn' => '',
        'location' => '',
        'type' => ImeiCashDeviceType::TYPE,
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

    $returnQuery = CashDevicesTable::returnQuery(
        CashDevicesTable::DEFAULT_SORT,
        CashDevicesTable::DEFAULT_DIR,
    );

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Cash Devices', false)
        ->assertSee('Phone Spec', false)
        ->assertSee('Apple', false)
        ->assertSee('iPhone 14', false)
        ->assertSee('128GB Black, excellent condition', false)
        ->assertSee('9,999', false)
        ->assertSee(route('imeis.edit', $cashDevice).'?return_query='.rawurlencode($returnQuery), false)
        ->assertDontSee('358918502270222', false)
        ->assertDontSee('Samsung', false);
});

test('dashboard cash devices table can be sorted by column', function () {
    $user = User::factory()->create();

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270111',
        'stock_take_date' => '',
        'make' => 'Zebra',
        'model' => 'Z1',
        'sn' => '',
        'location' => '',
        'type' => ImeiCashDeviceType::TYPE,
        'status' => ImeiCashDeviceType::STATUS,
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
        'imei' => '358918502270222',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'A1',
        'sn' => '',
        'location' => '',
        'type' => ImeiCashDeviceType::TYPE,
        'status' => ImeiCashDeviceType::STATUS,
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
        ->get(route('dashboard', ['sort' => 'make', 'dir' => 'asc']))
        ->assertSuccessful()
        ->assertSeeInOrder(['Apple', 'Zebra'], false);
});

test('view from cash devices returns to dashboard on exit', function () {
    $user = User::factory()->create();

    $cashDevice = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270111',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'iPhone 14',
        'sn' => '',
        'location' => '',
        'type' => ImeiCashDeviceType::TYPE,
        'status' => ImeiCashDeviceType::STATUS,
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

    $returnQuery = CashDevicesTable::returnQuery('make', 'asc');

    $html = $this->actingAs($user)
        ->get(route('imeis.edit', $cashDevice).'?return_query='.rawurlencode($returnQuery))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('returnListUrl');
    expect($html)->toContain('return_to=dashboard');
    expect($html)->toContain('sort=make');
    expect($html)->toContain('dir=asc');
});
