<?php

namespace App\Rules;

use App\Support\ImeiDeletedStatus;
use App\Support\ImeiNormalizedLookup;
use App\Support\ImeiValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueNormalizedNonStandardImei implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $key = ImeiValidator::normalizeNonStandard(is_string($value) ? $value : '');
        if ($key === '') {
            return;
        }

        $existing = ImeiNormalizedLookup::find($key);

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
