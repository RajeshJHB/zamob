<?php

use App\Models\ServiceNote;
use App\Support\ServiceNoteDealDetails;

test('formats service note heading and body for deal details field', function () {
    $note = new ServiceNote([
        'note_number' => 18,
        'heading' => 'Router install',
        'body' => 'Installed fibre router in lounge.',
    ]);

    expect(ServiceNoteDealDetails::format($note))->toBe(
        "SN-18 Claim Reference No. CRN-\nRouter install\n\nInstalled fibre router in lounge."
    );
});

test('deal details with only heading still starts with note number', function () {
    $note = new ServiceNote([
        'note_number' => 5,
        'heading' => 'Quick follow-up',
        'body' => '',
    ]);

    expect(ServiceNoteDealDetails::format($note))->toBe("SN-5 Claim Reference No. CRN-\nQuick follow-up");
});

test('deal details with no heading or body is only the note number', function () {
    $note = new ServiceNote([
        'note_number' => 99,
        'heading' => '',
        'body' => '',
    ]);

    expect(ServiceNoteDealDetails::format($note))->toBe('SN-99 Claim Reference No. CRN-');
});
