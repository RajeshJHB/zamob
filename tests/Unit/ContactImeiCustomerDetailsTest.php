<?php

use App\Models\Contact;
use App\Support\ContactImeiCustomerDetails;

test('formats full contact on one line with company name and both telephones', function () {
    $contact = new Contact([
        'company_name' => 'Acme',
        'first_name' => 'Jane',
        'surname' => 'Doe',
        'telephone_1' => '0829998888',
        'telephone_2' => '0831112222',
    ]);

    expect(ContactImeiCustomerDetails::format($contact))->toBe(
        'Acme, Jane Doe, Tel: 0829998888, 0831112222'
    );
});

test('omits company when empty and includes only first telephone when second is blank', function () {
    $contact = new Contact([
        'company_name' => '',
        'first_name' => 'Jane',
        'surname' => 'Doe',
        'telephone_1' => '0829998888',
        'telephone_2' => '',
    ]);

    expect(ContactImeiCustomerDetails::format($contact))->toBe(
        'Jane Doe, Tel: 0829998888'
    );
});
