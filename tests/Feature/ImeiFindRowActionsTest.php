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
});

test('receipt logo route serves jpg from resources', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('imeis.receipt.logo'))
        ->assertSuccessful()
        ->assertHeader('content-type', 'image/jpeg');
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
        ->assertSee('Edit IMEI', false)
        ->assertSee('358918502270284', false)
        ->assertSee('Samsung', false)
        ->assertSee('imei-submit-btn', false);
});
