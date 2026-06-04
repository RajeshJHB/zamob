<?php

use App\Support\ImeiLinkedServiceNote;

test('append imei to body adds line after existing text', function () {
    expect(ImeiLinkedServiceNote::appendImeiToBody('Customer visit.', '358918502270284'))->toBe(
        "Customer visit.\n\nIMEI: 358918502270284"
    );
});

test('append imei to body does not duplicate existing imei line', function () {
    $body = "Customer visit.\n\nIMEI: 358918502270284";

    expect(ImeiLinkedServiceNote::appendImeiToBody($body, '358918502270284'))->toBe($body);
});

test('append imei to empty body is only the imei line', function () {
    expect(ImeiLinkedServiceNote::appendImeiToBody('', 'NS-001'))->toBe('IMEI: NS-001');
});
