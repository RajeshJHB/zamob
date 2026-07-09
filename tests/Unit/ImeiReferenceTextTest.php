<?php

use App\Support\ImeiReferenceText;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('reference text helpers trim before comparing', function () {
    expect(ImeiReferenceText::equals('Oppo', 'Oppo '))->toBeTrue();
    expect(ImeiReferenceText::normalize('  Samsung  '))->toBe('Samsung');
});

test('make and model lookups ignore surrounding whitespace', function () {
    \Illuminate\Support\Facades\DB::table('imei_make')->insert([
        'make' => 'Oppo ',
        'date_added' => now(),
    ]);
    \Illuminate\Support\Facades\DB::table('imei_models')->insert([
        'make' => 'Oppo',
        'model' => 'A5 128GB 4G White',
        'serial' => '',
        'date_added' => now(),
    ]);

    expect(ImeiReferenceText::makeExists('Oppo'))->toBeTrue();
    expect(ImeiReferenceText::modelExistsForMake('Oppo ', 'A5 128GB 4G White'))->toBeTrue();
    expect(ImeiReferenceText::resolveStoredMake('Oppo'))->toBe('Oppo ');
});
