<?php

use App\Models\Imei;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

test('new imei date in is set to save time and ignores submitted value', function () {
    config(['app.timezone' => 'Africa/Johannesburg']);
    Carbon::setTestNow(Carbon::parse('2026-06-15 14:30:00', 'Africa/Johannesburg'));

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('imeis.store'), [
            'imei_non_standard' => '1',
            'imei' => 'dateintest01',
            'date_in' => '2020-01-01T08:00',
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
        ->assertRedirect();

    $imei = Imei::query()->where('imei', 'dateintest01')->firstOrFail();

    expect($imei->date_in->format('Y-m-d H:i'))->toBe('2026-06-15 14:30');
    expect($imei->date_updated->format('Y-m-d H:i'))->toBe('2026-06-15 14:30');
});
