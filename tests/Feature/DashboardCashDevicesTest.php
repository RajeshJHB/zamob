<?php

use App\Models\Imei;
use App\Models\ImeiSaleType;
use App\Models\User;
use App\Support\CashDevicesTable;
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

    if (! Schema::hasTable('imei_sale_types')) {
        Schema::create('imei_sale_types', function (Blueprint $table) {
            $table->id();
            $table->string('sale_type')->unique();
        });
    }

    foreach (['None', 'Cash', 'Voda_Sale', 'Easy20wn'] as $saleType) {
        ImeiSaleType::query()->firstOrCreate(['sale_type' => $saleType]);
    }
});

function createDashboardImei(array $overrides = []): Imei
{
    return Imei::query()->create(array_merge([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270'.fake()->unique()->numerify('###'),
        'cash_stock_type' => 'Cash',
        'make' => 'Make',
        'model' => 'Model',
        'sn' => '',
        'location' => '',
        'type' => 'Any type',
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
    ], $overrides));
}

test('guests are redirected from dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('dashboard lists matching sale type records and excludes sold', function () {
    $user = User::factory()->create();

    $visible = createDashboardImei([
        'imei' => '358918502270111',
        'cash_stock_type' => 'Cash',
        'make' => 'Apple',
        'model' => 'iPhone 14',
        'type' => 'Trade-in',
        'status' => 'In Shop',
        'ref' => '128GB Black, excellent condition',
        'selling_price' => 9999,
    ]);

    createDashboardImei([
        'imei' => '358918502270222',
        'cash_stock_type' => 'Cash',
        'make' => 'Samsung',
        'model' => 'Galaxy',
        'status' => 'Sold',
    ]);

    $returnQuery = CashDevicesTable::returnQuery(
        CashDevicesTable::DEFAULT_SORT,
        CashDevicesTable::DEFAULT_DIR,
    );

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Cash Devices', false)
        ->assertSee('Sale type', false)
        ->assertSee('>ALL</option>', false)
        ->assertSee('Apple', false)
        ->assertSee('iPhone 14', false)
        ->assertSee('9,999', false)
        ->assertSee(route('imeis.edit', $visible).'?return_query='.rawurlencode($returnQuery), false)
        ->assertDontSee('358918502270222', false)
        ->assertDontSee('Samsung', false);
});

test('dashboard all sale type shows records for any configured sale type except none', function () {
    $user = User::factory()->create();

    createDashboardImei([
        'cash_stock_type' => 'Cash',
        'make' => 'CashMake',
        'type' => 'New Cash device',
    ]);

    createDashboardImei([
        'cash_stock_type' => 'Voda_Sale',
        'make' => 'VodaMake',
        'type' => 'Contract',
    ]);

    createDashboardImei([
        'cash_stock_type' => 'None',
        'make' => 'NoneMake',
    ]);

    createDashboardImei([
        'cash_stock_type' => '',
        'make' => 'BlankMake',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('CashMake', false)
        ->assertSee('VodaMake', false)
        ->assertDontSee('NoneMake', false)
        ->assertDontSee('BlankMake', false);
});

test('dashboard can filter by a specific sale type regardless of device type', function () {
    $user = User::factory()->create();

    createDashboardImei([
        'cash_stock_type' => 'Cash',
        'make' => 'CashOnly',
        'type' => 'New Cash device',
    ]);

    createDashboardImei([
        'cash_stock_type' => 'Voda_Sale',
        'make' => 'VodaOnly',
        'type' => 'Other',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['sale_type' => 'Cash']))
        ->assertSuccessful()
        ->assertSee('CashOnly', false)
        ->assertDontSee('VodaOnly', false);
});

test('dashboard never shows sold records for matching sale type', function () {
    $user = User::factory()->create();

    createDashboardImei([
        'cash_stock_type' => 'Cash',
        'make' => 'AvailableCash',
        'status' => 'In Shop',
    ]);

    createDashboardImei([
        'cash_stock_type' => 'Cash',
        'make' => 'SoldCash',
        'status' => 'Sold',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['sale_type' => 'Cash']))
        ->assertSuccessful()
        ->assertSee('AvailableCash', false)
        ->assertDontSee('SoldCash', false);
});

test('dashboard cash devices table can be sorted by column', function () {
    $user = User::factory()->create();

    createDashboardImei([
        'cash_stock_type' => 'Cash',
        'make' => 'Zebra',
        'model' => 'Z1',
    ]);

    createDashboardImei([
        'cash_stock_type' => 'Cash',
        'make' => 'Apple',
        'model' => 'A1',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard', ['sort' => 'make', 'dir' => 'asc']))
        ->assertSuccessful()
        ->assertSeeInOrder(['Apple', 'Zebra'], false);
});

test('view from cash devices returns to dashboard on exit', function () {
    $user = User::factory()->create();

    $cashDevice = createDashboardImei([
        'cash_stock_type' => 'Cash',
        'make' => 'Apple',
        'model' => 'iPhone 14',
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
