<?php

use App\Support\ImeiFieldFilter;
use Illuminate\Http\Request;

test('imei field filter detects active pairs', function () {
    $inactive = Request::create('/', 'GET', [
        'field_filter_1' => '',
        'field_value_1' => '',
        'field_filter_2' => '',
        'field_value_2' => '',
    ]);

    expect(ImeiFieldFilter::hasActive($inactive))->toBeFalse();

    $active = Request::create('/', 'GET', [
        'field_filter_1' => 'status',
        'field_value_1' => 'In Shop',
        'field_filter_2' => '',
        'field_value_2' => '',
    ]);

    expect(ImeiFieldFilter::hasActive($active))->toBeTrue();
});

test('imei field filter rejects invalid field names', function () {
    $request = Request::create('/', 'GET', [
        'field_filter_1' => 'imei',
        'field_value_1' => '123',
    ]);

    expect(ImeiFieldFilter::pairFromRequest($request, 1))->toBeNull();
});

test('imei field filter supports exclude matching on filter three', function () {
    $request = Request::create('/', 'GET', [
        'field_filter_3' => 'status',
        'field_value_3' => 'Sold',
        'field_not_3' => '1',
    ]);

    $pair = ImeiFieldFilter::pairFromRequest($request, 3);

    expect($pair)->not->toBeNull()
        ->and($pair['field'])->toBe('status')
        ->and($pair['value'])->toBe('Sold')
        ->and($pair['exclude'])->toBeTrue();

    expect(ImeiFieldFilter::statusFilterValue($request))->toBeNull();
});

test('imei field filter maps sale type to cash stock type column', function () {
    expect(ImeiFieldFilter::databaseColumn(ImeiFieldFilter::FIELD_SALE_TYPE))->toBe('cash_stock_type');
});
