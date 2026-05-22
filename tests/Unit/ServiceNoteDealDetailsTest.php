<?php

use App\Models\ServiceNote;
use App\Support\ServiceNoteDealDetails;

test('formats service note heading and body for deal details field', function () {
    $note = new ServiceNote([
        'heading' => 'Router install',
        'body' => 'Installed fibre router in lounge.',
    ]);

    expect(ServiceNoteDealDetails::format($note))->toBe(
        "Router install\n\nInstalled fibre router in lounge."
    );
});
