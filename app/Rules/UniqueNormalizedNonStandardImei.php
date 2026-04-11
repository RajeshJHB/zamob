<?php

namespace App\Rules;

use App\Models\Imei;
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

        if (Imei::query()->whereNormalizedImei($key)->exists()) {
            $fail('An IMEI record with this number already exists.');
        }
    }
}
