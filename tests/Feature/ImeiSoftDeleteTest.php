<?php

use App\Models\Imei;
use App\Models\ImeiStatus;
use App\Models\User;
use App\Support\ImeiDeletedStatus;
use App\Support\ImeiNormalizedLookup;
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

function createSoftDeleteImei(string $imei, string $status = 'In Shop'): Imei
{
    return Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => $imei,
        'cash_stock_type' => '',
        'make' => 'Apple',
        'model' => 'A1',
        'sn' => '',
        'location' => 'Shop A',
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

test('role 4 user sees deleted status in find imei filter picklist', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    ImeiStatus::factory()->create(['status' => 'In Shop']);

    $this->actingAs($user)
        ->get(route('imeis.filter'))
        ->assertSuccessful()
        ->assertSee('"status":["In Shop","Deleted"]', false);
});

test('normal user does not see deleted status in find imei filter picklist', function () {
    $user = User::factory()->create();
    ImeiStatus::factory()->create(['status' => 'In Shop']);

    $html = $this->actingAs($user)
        ->get(route('imeis.filter'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('"status":["In Shop"]');
    expect($html)->not->toContain('"Deleted"');
});

test('role 4 user can find deleted records in search results', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    createSoftDeleteImei('111111111111111', ImeiDeletedStatus::VALUE);

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'field_filter_1' => 'status',
            'field_value_1' => ImeiDeletedStatus::VALUE,
        ]))
        ->assertSuccessful()
        ->assertSee('111111111111111', false);
});

test('normal user cannot find deleted records in search results', function () {
    $user = User::factory()->create();
    createSoftDeleteImei('222222222222222', ImeiDeletedStatus::VALUE);

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'field_filter_1' => 'status',
            'field_value_1' => ImeiDeletedStatus::VALUE,
        ]))
        ->assertSuccessful()
        ->assertDontSee('222222222222222', false);
});

test('any user soft deletes an imei record instead of removing it', function () {
    $user = User::factory()->create(['email' => 'staff@example.com']);
    $row = createSoftDeleteImei('333333333333333');

    $this->actingAs($user)
        ->from(route('imeis.edit', $row))
        ->delete(route('imeis.destroy', $row))
        ->assertRedirect(route('imeis.create'))
        ->assertSessionHas('message', 'IMEI record marked as deleted.');

    $fresh = $row->fresh();
    expect($fresh)->not->toBeNull();
    expect($fresh->status)->toBe(ImeiDeletedStatus::VALUE);
    expect($fresh->staff)->toBe('old@example.com, staff@example.com');
});

test('normal user cannot view a deleted imei record directly', function () {
    $user = User::factory()->create();
    $row = createSoftDeleteImei('444444444444444', ImeiDeletedStatus::VALUE);

    $this->actingAs($user)
        ->get(route('imeis.edit', $row))
        ->assertNotFound();
});

test('role 4 user can view a deleted imei record directly', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    $row = createSoftDeleteImei('555555555555555', ImeiDeletedStatus::VALUE);

    $this->actingAs($user)
        ->get(route('imeis.edit', $row))
        ->assertSuccessful()
        ->assertSee('Deleted', false);
});

test('cannot add an imei that matches a deleted record', function () {
    $user = User::factory()->create();
    createSoftDeleteImei('358918502270284', ImeiDeletedStatus::VALUE);

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            'imei_non_standard' => '0',
            'imei' => '358918502270284',
            'make' => '',
            'model' => '',
            'sn' => '',
            'location' => '',
            'type' => '',
            'status' => '',
            'notes' => '',
            'phonenumber' => '',
            'ref' => '',
            'item_code' => '',
            'ourON' => '',
            'salesON' => '',
            'cost_excl' => '',
            'selling_price' => '',
        ])
        ->assertSessionHasErrors('imei');

    expect(session('errors')->get('imei')[0])
        ->toBe(ImeiNormalizedLookup::DELETED_IMEI_MESSAGE);
});

test('lookup blocks a normal user from reusing a deleted imei', function () {
    $user = User::factory()->create();
    createSoftDeleteImei('358918502270284', ImeiDeletedStatus::VALUE);

    $this->actingAs($user)
        ->getJson(route('imeis.lookup', ['imei' => '358918502270284']))
        ->assertSuccessful()
        ->assertJson([
            'valid' => true,
            'exists' => true,
            'deleted' => true,
            'record' => null,
            'message' => ImeiNormalizedLookup::DELETED_IMEI_MESSAGE,
        ]);
});

test('lookup allows a role 4 user to load a deleted imei record', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    $row = createSoftDeleteImei('358918502270284', ImeiDeletedStatus::VALUE);

    $this->actingAs($user)
        ->getJson(route('imeis.lookup', ['imei' => '358918502270284']))
        ->assertSuccessful()
        ->assertJson([
            'valid' => true,
            'exists' => true,
            'deleted' => true,
            'message' => ImeiNormalizedLookup::DELETED_IMEI_ROLE4_MESSAGE,
            'record' => [
                'id' => $row->id,
                'imei' => '358918502270284',
            ],
        ]);
});

test('role 4 user cannot store a duplicate of a deleted imei', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    createSoftDeleteImei('358918502270284', ImeiDeletedStatus::VALUE);

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            'imei_non_standard' => '0',
            'imei' => '358918502270284',
            'make' => '',
            'model' => '',
            'sn' => '',
            'location' => '',
            'type' => '',
            'status' => '',
            'notes' => '',
            'phonenumber' => '',
            'ref' => '',
            'item_code' => '',
            'ourON' => '',
            'salesON' => '',
            'cost_excl' => '',
            'selling_price' => '',
        ])
        ->assertSessionHasErrors('imei');

    expect(session('errors')->get('imei')[0])
        ->toBe(ImeiNormalizedLookup::DELETED_IMEI_ROLE4_MESSAGE);
});
