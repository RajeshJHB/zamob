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
