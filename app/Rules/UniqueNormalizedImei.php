<?php

namespace App\Rules;

use App\Support\ImeiDeletedStatus;
use App\Support\ImeiNormalizedLookup;
use App\Support\ImeiValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueNormalizedImei implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = ImeiValidator::normalizeDigits(is_string($value) ? $value : '');
        if (! ImeiValidator::isValidChecksum($digits)) {
            return;
        }

        $existing = ImeiNormalizedLookup::find($digits);

        if ($existing === null) {
            return;
        }

        if (ImeiDeletedStatus::isDeleted($existing)) {
            $fail(ImeiNormalizedLookup::deletedImeiMessage());

            return;
        }

        $fail('An IMEI record with this number already exists.');
    }
}
