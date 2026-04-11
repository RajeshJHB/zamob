<?php

namespace App\Rules;

use App\Models\Imei;
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

        if (Imei::query()->whereNormalizedImei($digits)->exists()) {
            $fail('An IMEI record with this number already exists.');
        }
    }
}
