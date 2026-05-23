<?php

use App\Support\RepairServiceNoteBodyParser;

test('repair service note body parser extracts labeled fields', function () {
    $body = <<<'TEXT'
IMEI: 358918502270111
Make: Apple
Model: iPhone 14
Problem: Screen cracked
Price: R1500
Repair Place: zaMobile Blairgowrie
TEXT;

    expect(RepairServiceNoteBodyParser::parse($body))->toBe([
        'imei' => '358918502270111',
        'make' => 'Apple',
        'model' => 'iPhone 14',
        'problem' => 'Screen cracked',
        'price' => 'R1500',
        'repair_place' => 'zaMobile Blairgowrie',
    ]);
});

test('repair service note body parser returns empty fields for blank body', function () {
    expect(RepairServiceNoteBodyParser::parse(''))->toBe([
        'imei' => '',
        'make' => '',
        'model' => '',
        'problem' => '',
        'price' => '',
        'repair_place' => '',
    ]);
});
