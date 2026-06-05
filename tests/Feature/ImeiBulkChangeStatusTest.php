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

/**
 * @return array<string, mixed>
 */
function bulkStatusFilterParams(string $fromStatus = 'Error', string $toLocation = ''): array
{
    $params = [
        'field_filter_1' => 'status',
        'field_value_1' => $fromStatus,
    ];

    if ($toLocation !== '') {
        $params['field_filter_2'] = 'location';
        $params['field_value_2'] = $toLocation;
    }

    return $params;
}

function createImeiRecord(string $imei, string $status, string $location = ''): Imei
{
    return Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => $imei,
        'cash_stock_type' => '',
        'make' => 'Apple',
        'model' => 'A1',
        'sn' => '',
        'location' => $location,
        'type' => 'Cash',
        'status' => $status,
        'notes' => '',
        'phonenumber' => '',
        'ref' => '',
        'staff' => 'old@example.com',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '',
        'selling_price' => null,
    ]);
}

test('advanced bulk status controls are hidden without role 4 even when status filter is active', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'Error']);

    $this->actingAs($user)
        ->get(route('imeis.index', bulkStatusFilterParams()))
        ->assertSuccessful()
        ->assertDontSee('id="imei-advanced-menu-button"', false)
        ->assertDontSee('Bulk change Status', false);
});

test('advanced bulk status controls are hidden for role 4 without status field filter', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    ImeiStatus::factory()->create(['status' => 'Error']);

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'field_filter_1' => 'location',
            'field_value_1' => 'Shop A',
        ]))
        ->assertSuccessful()
        ->assertDontSee('id="imei-advanced-menu-button"', false);
});

test('role 4 user sees advanced bulk status controls when status field filter matches more than one record', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    ImeiStatus::factory()->create(['status' => 'Error']);
    ImeiStatus::factory()->create(['status' => 'Unknown']);
    createImeiRecord('111111111111111', 'Error');
    createImeiRecord('222222222222222', 'Error');

    $this->actingAs($user)
        ->get(route('imeis.index', bulkStatusFilterParams()))
        ->assertSuccessful()
        ->assertSee('id="imei-advanced-menu-button"', false)
        ->assertSee('Bulk change Status', false)
        ->assertSee('Change status for', false)
        ->assertSee('from <strong>Error</strong>', false);
});

test('role 4 user does not see advanced bulk status controls when status filter matches only one record', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    ImeiStatus::factory()->create(['status' => 'Error']);
    createImeiRecord('111111111111111', 'Error');

    $this->actingAs($user)
        ->get(route('imeis.index', bulkStatusFilterParams()))
        ->assertSuccessful()
        ->assertDontSee('id="imei-advanced-menu-button"', false)
        ->assertDontSee('Bulk change Status', false);
});

test('role 4 user can bulk change status for filtered search results only', function () {
    $user = User::factory()->create(['email' => 'admin@example.com']);
    grantRoleFourForImeiReferenceDeletes($user);
    ImeiStatus::factory()->create(['status' => 'Error']);
    ImeiStatus::factory()->create(['status' => 'Unknown']);

    $matching = createImeiRecord('111111111111111', 'Error', 'Shop A');
    $otherStatus = createImeiRecord('222222222222222', 'Sold', 'Shop A');
    $otherLocation = createImeiRecord('333333333333333', 'Error', 'Shop B');

    $this->actingAs($user)
        ->post(route('imeis.bulk-status'), array_merge(bulkStatusFilterParams('Error', 'Shop A'), [
            'status_to' => 'Unknown',
        ]))
        ->assertRedirect(route('imeis.index', bulkStatusFilterParams('Error', 'Shop A')))
        ->assertSessionHas('message', 'Updated 1 record(s) from Error to Unknown.');

    expect($matching->fresh()->status)->toBe('Unknown');
    expect($matching->fresh()->staff)->toBe('old@example.com, admin@example.com');
    expect($otherStatus->fresh()->status)->toBe('Sold');
    expect($otherLocation->fresh()->status)->toBe('Error');
});

test('users without role 4 cannot bulk change imei status', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'Error']);
    ImeiStatus::factory()->create(['status' => 'Unknown']);
    $record = createImeiRecord('111111111111111', 'Error');

    $this->actingAs($user)
        ->post(route('imeis.bulk-status'), array_merge(bulkStatusFilterParams(), [
            'status_to' => 'Unknown',
        ]))
        ->assertForbidden();

    expect($record->fresh()->status)->toBe('Error');
});

test('bulk status change requires a status field filter', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    ImeiStatus::factory()->create(['status' => 'Unknown']);
    $record = createImeiRecord('111111111111111', 'Error');

    $this->actingAs($user)
        ->post(route('imeis.bulk-status'), [
            'status_to' => 'Unknown',
        ])
        ->assertSessionHasErrors('field_filter');

    expect($record->fresh()->status)->toBe('Error');
});

test('role 4 user bulk status change updates all matching records not just the first chunk', function () {
    $user = User::factory()->create(['email' => 'admin@example.com']);
    grantRoleFourForImeiReferenceDeletes($user);
    ImeiStatus::factory()->create(['status' => 'Error']);
    ImeiStatus::factory()->create(['status' => 'Unknown']);

    for ($i = 1; $i <= 250; $i++) {
        createImeiRecord(sprintf('1111111111%05d', $i), 'Error');
    }

    createImeiRecord('999999999999999', 'Sold');

    $this->actingAs($user)
        ->post(route('imeis.bulk-status'), array_merge(bulkStatusFilterParams('Error'), [
            'status_to' => 'Unknown',
        ]))
        ->assertRedirect(route('imeis.index', bulkStatusFilterParams('Error')))
        ->assertSessionHas('message', 'Updated 250 record(s) from Error to Unknown.');

    expect(Imei::query()->where('status', 'Unknown')->count())->toBe(250);
    expect(Imei::query()->where('status', 'Error')->count())->toBe(0);
    expect(Imei::query()->where('imei', '999999999999999')->value('status'))->toBe('Sold');
});

test('bulk status change rejects choosing the same status as the filter', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    ImeiStatus::factory()->create(['status' => 'Error']);
    $record = createImeiRecord('111111111111111', 'Error');

    $this->actingAs($user)
        ->post(route('imeis.bulk-status'), array_merge(bulkStatusFilterParams(), [
            'status_to' => 'Error',
        ]))
        ->assertSessionHasErrors('status_to');

    expect($record->fresh()->status)->toBe('Error');
});
