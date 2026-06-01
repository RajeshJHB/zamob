<?php

use App\Support\ImeiCostIncl;

test('cost incl is calculated from cost excl and vat percent without database', function () {
    expect(ImeiCostIncl::format('100', 15))->toBe('115');
    expect(ImeiCostIncl::format('100', 20))->toBe('120');
});

test('invalid cost excl returns null for cost incl', function () {
    expect(ImeiCostIncl::format(''))->toBeNull();
    expect(ImeiCostIncl::format('abc'))->toBeNull();
});

test('cost excl with comma decimal is parsed', function () {
    expect(ImeiCostIncl::format('99,50', 10))->toBe('109.45');
});

test('sum inclusive totals parsed cost excl values with vat', function () {
    expect(ImeiCostIncl::formatTotal(ImeiCostIncl::sumInclusive(['100', '50'], 15)))->toBe('172.5');
});

test('sum inclusive ignores invalid cost excl values', function () {
    expect(ImeiCostIncl::formatTotal(ImeiCostIncl::sumInclusive(['100', '', 'abc'], 15)))->toBe('115');
});
