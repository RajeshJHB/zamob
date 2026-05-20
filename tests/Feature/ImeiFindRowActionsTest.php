<?php

use App\Models\Imei;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * @return list<int>
 */
function seedImeiBrowseRows(int $count, string $prefix = 'BROWSE'): array
{
    $ids = [];
    $now = now();

    for ($i = 0; $i < $count; $i++) {
        $ids[] = Imei::query()->create([
            'date_in' => $now->copy()->subMinutes($i),
            'date_updated' => $now,
            'imei' => $prefix.$i,
            'stock_take_date' => '',
            'make' => 'Make',
            'model' => 'Model',
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
        ])->id;
    }

    return $ids;
}

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

test('imei index uses full width main content area', function () {
    $user = User::factory()->create();

    $html = $this->actingAs($user)
        ->get(route('imeis.index'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('w-full max-w-none');
});

test('view link from index preserves list filter state for exit return', function () {
    $user = User::factory()->create();
    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270284',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'iPhone',
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

    $html = $this->actingAs($user)
        ->get(route('imeis.index', [
            'search' => '358918502270284',
            'scope' => 'all',
            'sort1_column' => 'make',
            'sort1_dir' => 'asc',
        ]))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('return_query=');
    expect($html)->toContain(rawurlencode('search=358918502270284'));
});

test('view page exposes return list url from return_query', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270284',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'iPhone',
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

    $returnQuery = http_build_query([
        'search' => '358918502270284',
        'scope' => 'all',
        'sort1_column' => 'make',
        'sort1_dir' => 'asc',
    ]);

    $html = $this->actingAs($user)
        ->get(route('imeis.edit', $row).'?return_query='.rawurlencode($returnQuery))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('returnListUrl');
    expect($html)->toContain('returnQueryEncoded');
    expect($html)->toContain('search=358918502270284');
    expect($html)->toContain('sort1_column=make');
});

test('update redirects back to view with return_query preserved', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270284',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'iPhone',
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

    $returnQuery = http_build_query([
        'search' => '358918502270284',
        'scope' => 'all',
    ]);

    $this->actingAs($user)
        ->put(route('imeis.update', $row), [
            '_token' => csrf_token(),
            'return_query' => $returnQuery,
            'date_in' => '',
            'notes' => 'changed',
        ])
        ->assertRedirect(route('imeis.edit', $row).'?return_query='.rawurlencode($returnQuery));
});

test('find results include print and edit links per row', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270284',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'iPhone',
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

    $html = $this->actingAs($user)
        ->get(route('imeis.index'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain(route('imeis.receipt', $row));
    expect($html)->toContain(route('imeis.edit', $row));
    expect($html)->toContain('>View</a>');
    expect($html)->not->toContain('name="_method" value="DELETE"');
});

test('edit imei page includes delete for users with role 4', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270284',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'iPhone',
        'sn' => '',
        'location' => '',
        'type' => '',
        'status' => '',
        'notes' => 'Keep',
        'phonenumber' => '',
        'ref' => '',
        'staff' => '',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '',
        'selling_price' => null,
    ]);

    $html = $this->actingAs($user)
        ->get(route('imeis.edit', $row))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('name="_method" value="DELETE"');
    expect($html)->toContain('imei-delete-btn-top');
});

test('edit imei page does not include delete without role 4', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270299',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'iPhone',
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

    $html = $this->actingAs($user)
        ->get(route('imeis.edit', $row))
        ->assertSuccessful()
        ->getContent();

    expect($html)->not->toContain('imei-delete-form');
});

test('users without role 4 cannot delete an imei record', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270285',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'iPhone',
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
        ->from(route('imeis.index'))
        ->delete(route('imeis.destroy', $row))
        ->assertForbidden();

    expect(Imei::query()->find($row->id))->not->toBeNull();
});

test('users with role 4 can delete an imei record', function () {
    $user = User::factory()->create();
    grantRoleFourForImeiReferenceDeletes($user);
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270286',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'iPhone',
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
        ->from(route('imeis.edit', $row))
        ->delete(route('imeis.destroy', $row))
        ->assertRedirect(route('imeis.create'));

    expect(Imei::query()->find($row->id))->toBeNull();
});

test('receipt logo route serves png from resources', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('imeis.receipt.logo'))
        ->assertSuccessful()
        ->assertHeader('content-type', 'image/png');
});

test('receipt page shows device receipt fields for an imei', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270284',
        'stock_take_date' => '',
        'make' => 'Apple',
        'model' => 'iPhone 17',
        'sn' => '195950623888',
        'location' => '',
        'type' => '',
        'status' => '',
        'notes' => "Line one\nLine two",
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
        ->get(route('imeis.receipt', $row))
        ->assertSuccessful()
        ->assertSee('Device Receipt', false)
        ->assertSee('358918502270284', false)
        ->assertSee('Apple', false)
        ->assertSee('195950623888', false)
        ->assertSee('Terms and Conditions Apply', false);
});

test('edit from find loads create form with record in read-only mode', function () {
    $user = User::factory()->create();
    $row = Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => '358918502270284',
        'stock_take_date' => '',
        'make' => 'Samsung',
        'model' => 'Galaxy',
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
        ->get(route('imeis.edit', $row))
        ->assertSuccessful()
        ->assertSee('View IMEI', false)
        ->assertSee('358918502270284', false)
        ->assertSee('Samsung', false)
        ->assertSee('imei-submit-btn', false);
});

test('unfiltered find imei search shows only the latest two hundred records', function () {
    $user = User::factory()->create();
    seedImeiBrowseRows(250);

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'scope' => 'all',
            'date_scope' => 'all',
        ]))
        ->assertSuccessful()
        ->assertViewHas('imeis', fn ($paginator) => $paginator->total() === 200);
});

test('unfiltered find imei search with no query params also limits to two hundred records', function () {
    $user = User::factory()->create();
    seedImeiBrowseRows(250);

    $this->actingAs($user)
        ->get(route('imeis.index'))
        ->assertSuccessful()
        ->assertViewHas('imeis', fn ($paginator) => $paginator->total() === 200);
});

test('find imei text search is not capped at two hundred records', function () {
    $user = User::factory()->create();
    seedImeiBrowseRows(250);

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'search' => 'BROWSE',
            'scope' => 'all',
            'date_scope' => 'all',
        ]))
        ->assertSuccessful()
        ->assertViewHas('imeis', fn ($paginator) => $paginator->total() === 250);
});

test('find imei date range search is not capped at two hundred records', function () {
    $user = User::factory()->create();
    seedImeiBrowseRows(250);
    $start = now()->subDays(2)->format('Y-m-d');
    $end = now()->format('Y-m-d');

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'scope' => 'all',
            'date_scope' => 'range',
            'date_column' => 'date_in',
            'start_date' => $start,
            'end_date' => $end,
        ]))
        ->assertSuccessful()
        ->assertViewHas('imeis', fn ($paginator) => $paginator->total() === 250);
});

test('find imei with custom sort is not capped at two hundred records', function () {
    $user = User::factory()->create();
    seedImeiBrowseRows(250);

    $this->actingAs($user)
        ->get(route('imeis.index', [
            'scope' => 'all',
            'date_scope' => 'all',
            'sort1_column' => 'make',
            'sort1_dir' => 'asc',
        ]))
        ->assertSuccessful()
        ->assertViewHas('imeis', fn ($paginator) => $paginator->total() === 250);
});
