<?php

namespace App\Support;

use App\Models\Contact;

final class ContactImeiCustomerDetails
{
    public static function format(Contact $contact): string
    {
        $parts = [];

        $company = trim((string) $contact->company_name);
        if ($company !== '') {
            $parts[] = $company;
        }

        $name = trim(trim((string) $contact->first_name).' '.trim((string) $contact->surname));
        if ($name !== '') {
            $parts[] = $name;
        }

        $telephones = array_values(array_filter([
            trim((string) $contact->telephone_1),
            trim((string) $contact->telephone_2),
        ], fn (string $value): bool => $value !== ''));

        if ($telephones !== []) {
            $parts[] = 'Tel: '.implode(', ', $telephones);
        }

        return implode(', ', $parts);
    }
}
