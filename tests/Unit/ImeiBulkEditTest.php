<?php

use App\Support\ImeiBulkEdit;

test('remove search text strips matching text and collapses spaces', function () {
    expect(ImeiBulkEdit::removeSearchTextFromField('Contact Tracy - iPhone', 'Tracy'))
        ->toBe('Contact - iPhone');
});

test('replace search text is case insensitive', function () {
    expect(ImeiBulkEdit::replaceSearchTextInField('contact tracy phone', 'tracy', 'Tracy Chapman'))
        ->toBe('contact Tracy Chapman phone');
});

test('should replace search text when checkbox set and replace provided', function () {
    expect(ImeiBulkEdit::shouldReplaceSearchText([
        'search_customer_details' => 'tracy',
        'replace_customer_details' => 'Tracy Chapman',
        'replace_search_text_customer_details' => '1',
    ], ImeiBulkEdit::FIELD_CUSTOMER_DETAILS))->toBeTrue();
});

test('remove search text is case insensitive', function () {
    expect(ImeiBulkEdit::removeSearchTextFromField('contact TRACY phone', 'tracy'))
        ->toBe('contact phone');
});

test('build update attributes keeps date updated when requested', function () {
    $now = new \DateTimeImmutable('2026-06-15 12:00:00');

    $attributes = ImeiBulkEdit::buildUpdateAttributes(
        [],
        $now,
        [],
        keepDateUpdated: true,
    );

    expect($attributes)->not->toHaveKey('date_updated');
});

test('build update attributes uses explicit replace date updated over keep flag', function () {
    $now = new \DateTimeImmutable('2026-06-15 12:00:00');
    $replaceDate = new \DateTimeImmutable('2025-01-10 08:30:00');

    $attributes = ImeiBulkEdit::buildUpdateAttributes(
        [],
        $now,
        [],
        keepDateUpdated: true,
        replaceDateUpdated: $replaceDate,
    );

    expect($attributes['date_updated'])->toEqual($replaceDate);
});

test('build update attributes defaults date updated to now', function () {
    $now = new \DateTimeImmutable('2026-06-15 12:00:00');

    $attributes = ImeiBulkEdit::buildUpdateAttributes([], $now);

    expect($attributes['date_updated'])->toEqual($now);
});

test('replace date updated counts as a replace action', function () {
    expect(ImeiBulkEdit::hasAnyReplaceAction([
        'replace_date_updated' => '2026-01-15T09:00',
    ]))->toBeTrue();
});

test('normalized replace date updated accepts past dates', function () {
    $parsed = ImeiBulkEdit::normalizedReplaceDateUpdated([
        'replace_date_updated' => '2020-06-01 09:15',
    ]);

    expect($parsed)->not->toBeNull()
        ->and($parsed?->format('Y-m-d H:i'))->toBe('2020-06-01 09:15');
});

test('should remove search text when checkbox set and replace empty', function () {
    expect(ImeiBulkEdit::shouldRemoveSearchText([
        'search_customer_details' => 'Tracy',
        'replace_customer_details' => '',
        'remove_search_customer_details' => '1',
    ], ImeiBulkEdit::FIELD_CUSTOMER_DETAILS))->toBeTrue();

    expect(ImeiBulkEdit::shouldRemoveSearchText([
        'search_customer_details' => 'Tracy',
        'replace_customer_details' => 'New value',
        'remove_search_customer_details' => '1',
    ], ImeiBulkEdit::FIELD_CUSTOMER_DETAILS))->toBeFalse();
});
