<?php

use App\Support\RepairServiceNoteTemplate;

test('repair service note heading template is repair', function () {
    expect(RepairServiceNoteTemplate::HEADING)->toBe('Repair');
});

test('repair service note body template has expected lines', function () {
    $lines = explode("\n", RepairServiceNoteTemplate::BODY);

    expect($lines)->toBe([
        'IMEI: ',
        'Make:',
        'Model:',
        'Problem:',
        'Price:',
        'Repair Place: ',
    ]);
});

test('repair type name matching is case insensitive', function () {
    expect(RepairServiceNoteTemplate::isRepairTypeName('Repair'))->toBeTrue();
    expect(RepairServiceNoteTemplate::isRepairTypeName('repair'))->toBeTrue();
    expect(RepairServiceNoteTemplate::isRepairTypeName('Consult'))->toBeFalse();
});
