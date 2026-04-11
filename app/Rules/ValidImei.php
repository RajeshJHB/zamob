<?php

namespace App\Rules;

use App\Support\ImeiValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidImei implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! ImeiValidator::isValidChecksum(is_string($value) ? $value : '')) {
            $fail('The :attribute must be a valid 15-digit IMEI (check digit must be correct).');
        }
    }
}
