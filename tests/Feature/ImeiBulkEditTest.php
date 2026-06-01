<?php

use App\Models\Imei;
use App\Models\ImeiSaleType;
use App\Models\ImeiStatus;
use App\Models\ImeiType;
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

function createBulkEditImei(string $imei, array $overrides = []): Imei
{
    return Imei::query()->create(array_merge([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => $imei,
        'cash_stock_type' => 'Cash',
        'make' => 'Apple',
        'model' => 'A1',
        'sn' => '',
        'location' => '',
        'type' => 'Cash',
        'status' => 'Available',
        'notes' => 'Old customer',
        'phonenumber' => '',
        'ref' => 'Deal-A',
        'staff' => 'old@example.com',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '',
        'selling_price' => null,
    ], $overrides));
}

test('bulk edit button is hidden without role 5', function () {
    $user = User::factory()->create();
    grantApplicationAccess($user);

    $this->actingAs($user)
        ->get(route('imeis.index'))
        ->assertSuccessful()
        ->assertDontSee('id="imei-bulk-edit-open"', false)
        ->assertDontSee('Bulk Edit', false);
});

test('bulk edit button is visible for role 5', function () {
    $user = User::factory()->create();
    grantRoleFiveForImeiBulkEdit($user);

    $this->actingAs($user)
        ->get(route('imeis.index'))
        ->assertSuccessful()
        ->assertSee('id="imei-bulk-edit-open"', false)
        ->assertSee('Bulk Edit', false);
});

test('users without role 5 cannot bulk edit imei records', function () {
    $user = User::factory()->create();
    grantApplicationAccess($user);
    createBulkEditImei('111111111111111');

    $this->actingAs($user)
        ->post(route('imeis.bulk-edit'), [
            'search_status' => 'Available',
            'replace_status' => 'Sold',
        ])
        ->assertForbidden();
});

test('bulk edit updates matching records within current filters', function () {
    $user = User::factory()->create(['email' => 'editor@example.com']);
    grantRoleFiveForImeiBulkEdit($user);

    ImeiStatus::query()->firstOrCreate(['status' => 'Available']);
    ImeiStatus::query()->firstOrCreate(['status' => 'Sold']);
    ImeiSaleType::query()->firstOrCreate(['sale_type' => 'Cash']);
    ImeiSaleType::query()->firstOrCreate(['sale_type' => 'Finance']);

    $match = createBulkEditImei('111111111111111', [
        'status' => 'Available',
        'ref' => 'Deal-A',
        'notes' => 'Old customer',
    ]);
    $otherStatus = createBulkEditImei('222222222222222', [
        'status' => 'Sold',
        'ref' => 'Deal-A',
        'notes' => 'Old customer',
    ]);
    $otherDeal = createBulkEditImei('333333333333333', [
        'status' => 'Available',
        'ref' => 'Deal-B',
        'notes' => 'Old customer',
    ]);

    $this->actingAs($user)
        ->post(route('imeis.bulk-edit'), [
            'field_filter_1' => 'status',
            'field_value_1' => 'Available',
            'search_deal_details' => 'Deal-A',
            'replace_status' => 'Sold',
            'replace_deal_details' => 'Deal-Updated',
            'replace_customer_details' => 'New customer',
            'replace_sale_type' => 'Finance',
        ])
        ->assertRedirect(route('imeis.index', [
            'field_filter_1' => 'status',
            'field_value_1' => 'Available',
        ]))
        ->assertSessionHas('message');

    $match->refresh();
    $otherStatus->refresh();
    $otherDeal->refresh();

    expect($match->status)->toBe('Sold')
        ->and($match->ref)->toBe('Deal-Updated')
        ->and($match->notes)->toBe('New customer')
        ->and($match->cash_stock_type)->toBe('Finance')
        ->and($match->staff)->toContain('editor@example.com');

    expect($otherStatus->status)->toBe('Sold')
        ->and($otherStatus->ref)->toBe('Deal-A');

    expect($otherDeal->status)->toBe('Available')
        ->and($otherDeal->ref)->toBe('Deal-B');
});

test('bulk edit uses contains matching for customer and deal details search', function () {
    $user = User::factory()->create(['email' => 'editor@example.com']);
    grantRoleFiveForImeiBulkEdit($user);

    ImeiSaleType::query()->firstOrCreate(['sale_type' => 'Cash']);
    ImeiSaleType::query()->firstOrCreate(['sale_type' => 'Online_Only']);

    $tracyMatch = createBulkEditImei('111111111111111', [
        'notes' => 'Contact Tracy - iPhone',
        'cash_stock_type' => 'Cash',
    ]);
    $exactOnly = createBulkEditImei('222222222222222', [
        'notes' => 'John Smith',
        'cash_stock_type' => 'Cash',
    ]);
    $dealMatch = createBulkEditImei('333333333333333', [
        'notes' => 'Other customer',
        'ref' => 'Promo-Deal-A',
        'cash_stock_type' => 'Cash',
    ]);

    $this->actingAs($user)
        ->post(route('imeis.bulk-edit'), [
            'search_customer_details' => 'Tracy',
            'replace_sale_type' => 'Online_Only',
        ])
        ->assertRedirect(route('imeis.index'))
        ->assertSessionHas('message');

    $tracyMatch->refresh();
    $exactOnly->refresh();
    $dealMatch->refresh();

    expect($tracyMatch->cash_stock_type)->toBe('Online_Only')
        ->and($exactOnly->cash_stock_type)->toBe('Cash')
        ->and($dealMatch->cash_stock_type)->toBe('Cash');

    $this->actingAs($user)
        ->post(route('imeis.bulk-edit'), [
            'search_deal_details' => 'Deal-A',
            'replace_sale_type' => 'Online_Only',
        ])
        ->assertRedirect(route('imeis.index'))
        ->assertSessionHas('message');

    $dealMatch->refresh();

    expect($dealMatch->cash_stock_type)->toBe('Online_Only');
});

test('bulk edit can exclude records matching a status from search', function () {
    $user = User::factory()->create();
    grantRoleFiveForImeiBulkEdit($user);

    ImeiStatus::query()->firstOrCreate(['status' => 'Sold']);
    ImeiStatus::query()->firstOrCreate(['status' => 'In Shop']);

    $sold = createBulkEditImei('111111111111111', ['status' => 'Sold']);
    $inShop = createBulkEditImei('222222222222222', ['status' => 'In Shop']);

    $this->actingAs($user)
        ->post(route('imeis.bulk-edit'), [
            'search_status' => 'Sold',
            'search_not_status' => '1',
            'replace_status' => 'In Shop',
        ])
        ->assertRedirect(route('imeis.index'))
        ->assertSessionHas('message');

    expect($sold->fresh()->status)->toBe('Sold');
    expect($inShop->fresh()->status)->toBe('In Shop');
});

test('bulk edit can search and replace by type', function () {
    $user = User::factory()->create();
    grantRoleFiveForImeiBulkEdit($user);

    ImeiType::factory()->create(['type' => 'Trade-in']);
    ImeiType::factory()->create(['type' => 'New Cash device']);

    $match = createBulkEditImei('111111111111111', ['type' => 'Trade-in']);
    $other = createBulkEditImei('222222222222222', ['type' => 'New Cash device']);

    $this->actingAs($user)
        ->post(route('imeis.bulk-edit'), [
            'search_type' => 'Trade-in',
            'replace_type' => 'New Cash device',
        ])
        ->assertRedirect(route('imeis.index'))
        ->assertSessionHas('message');

    expect($match->fresh()->type)->toBe('New Cash device');
    expect($other->fresh()->type)->toBe('New Cash device');
});

test('bulk edit can replace search text in customer details while keeping the rest', function () {
    $user = User::factory()->create(['email' => 'editor@example.com']);
    grantRoleFiveForImeiBulkEdit($user);

    $match = createBulkEditImei('111111111111111', [
        'notes' => 'Contact tracy - iPhone 15',
    ]);
    $noMatch = createBulkEditImei('222222222222222', [
        'notes' => 'John Smith',
    ]);

    $this->actingAs($user)
        ->post(route('imeis.bulk-edit'), [
            'search_customer_details' => 'tracy',
            'replace_customer_details' => 'Tracy Chapman',
            'replace_search_text_customer_details' => '1',
        ])
        ->assertRedirect(route('imeis.index'))
        ->assertSessionHas('message');

    expect($match->fresh()->notes)->toBe('Contact Tracy Chapman - iPhone 15');
    expect($noMatch->fresh()->notes)->toBe('John Smith');
});

test('bulk edit can remove search text from customer details while keeping the rest', function () {
    $user = User::factory()->create(['email' => 'editor@example.com']);
    grantRoleFiveForImeiBulkEdit($user);

    $match = createBulkEditImei('111111111111111', [
        'notes' => 'Contact Tracy - iPhone 15',
    ]);
    $noMatch = createBulkEditImei('222222222222222', [
        'notes' => 'John Smith',
    ]);

    $this->actingAs($user)
        ->post(route('imeis.bulk-edit'), [
            'search_customer_details' => 'Tracy',
            'remove_search_customer_details' => '1',
        ])
        ->assertRedirect(route('imeis.index'))
        ->assertSessionHas('message');

    expect($match->fresh()->notes)->toBe('Contact - iPhone 15');
    expect($noMatch->fresh()->notes)->toBe('John Smith');
});

test('bulk edit remove search text requires matching search value', function () {
    $user = User::factory()->create();
    grantRoleFiveForImeiBulkEdit($user);

    $this->actingAs($user)
        ->from(route('imeis.index'))
        ->post(route('imeis.bulk-edit'), [
            'remove_search_customer_details' => '1',
        ])
        ->assertSessionHasErrors('search_customer_details');
});

test('bulk edit requires at least one search and one replace value', function () {
    $user = User::factory()->create();
    grantRoleFiveForImeiBulkEdit($user);

    $this->actingAs($user)
        ->from(route('imeis.index'))
        ->post(route('imeis.bulk-edit'), [])
        ->assertSessionHasErrors(['search_sale_type', 'replace_sale_type']);
});
