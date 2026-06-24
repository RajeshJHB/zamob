<?php

use App\Models\AppSetting;
use App\Models\Imei;
use App\Models\User;
use App\Support\ImeiCostIncl;
use App\Support\ImeiCsvExporter;
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

test('guest is redirected from imei csv export', function () {
    $this->get(route('imeis.export'))->assertRedirect(route('login'));
});

test('imei index shows export csv action', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('imeis.index'))
        ->assertSuccessful()
        ->assertSee('Export CSV', false)
        ->assertSee(route('imeis.export'), false);
});

test('imei csv export streams filtered rows with selected columns', function () {
    $user = User::factory()->create();

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'EXPORTKEEP999',
        'cash_stock_type' => '',
        'make' => 'Samsung',
        'model' => 'A55',
        'sn' => '00123',
        'location' => '',
        'type' => '',
        'status' => 'In stock',
        'notes' => 'Full customer details here',
        'phonenumber' => '',
        'ref' => 'Deal ref text',
        'staff' => '',
        'item_code' => '',
        'ourON' => '',
        'salesON' => '',
        'cost_excl' => '100',
        'selling_price' => null,
    ]);

    Imei::query()->create([
        'date_in' => now(),
        'date_updated' => now(),
        'imei' => 'EXPORTSKIP888',
        'cash_stock_type' => '',
        'make' => 'Other',
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

    $response = $this->actingAs($user)
        ->get(route('imeis.export', [
            'search' => 'EXPORTKEEP999',
            'scope' => 'selected',
            'columns' => ['make', 'imei', 'cost_incl', 'notes'],
            'date_scope' => 'all',
        ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('text/csv');

    $content = $response->streamedContent();

    expect($content)->toContain('Make');
    expect($content)->toContain('IMEI');
    expect($content)->toContain('Cost incl');
    expect($content)->toContain('Customer Details');
    expect($content)->toContain('Samsung');
    expect($content)->toContain("\tEXPORTKEEP999");
    expect($content)->toContain(ImeiCostIncl::format('100'));
    expect($content)->toContain('Full customer details here');
    expect($content)->not->toContain('EXPORTSKIP888');
    expect($content)->not->toContain('Other');
});

test('imei csv export is not capped by browse list limit', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        Imei::query()->create([
            'date_in' => now()->subMinutes($i),
            'date_updated' => now()->subMinutes($i),
            'imei' => 'UNLIMITED'.$i,
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
    }

    AppSetting::setBrowseListLimit(2);

    $content = $this->actingAs($user)
        ->get(route('imeis.export', [
            'scope' => 'all',
            'date_scope' => 'all',
            'columns' => ['imei'],
        ]))
        ->assertSuccessful()
        ->streamedContent();

    expect($content)->toContain('UNLIMITED0');
    expect($content)->toContain('UNLIMITED4');
});

test('imei csv exporter formats cell values like print view', function () {
    $imei = Imei::query()->make([
        'date_in' => now()->setDateTime(2026, 3, 15, 9, 30),
        'date_updated' => now()->setDateTime(2026, 3, 16, 14, 45),
        'imei' => '012345678901234',
        'sn' => '00045',
        'cost_excl' => '200',
        'notes' => 'Note body',
    ]);

    expect(ImeiCsvExporter::cellValue($imei, 'date_in'))->toBe('2026-03-15 09:30');
    expect(ImeiCsvExporter::cellValue($imei, 'cost_incl'))->toBe(ImeiCostIncl::format('200'));
    expect(ImeiCsvExporter::cellValue($imei, 'imei'))->toBe("\t012345678901234");
    expect(ImeiCsvExporter::cellValue($imei, 'sn'))->toBe("\t00045");
    expect(ImeiCsvExporter::cellValue($imei, 'notes'))->toBe('Note body');
});
