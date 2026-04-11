<?php

namespace App\Http\Requests;

use App\Rules\UniqueNormalizedImei;
use App\Rules\ValidImei;
use App\Support\ImeiOptionalStringFields;
use App\Support\ImeiValidator;
use Illuminate\Foundation\Http\FormRequest;

class StoreImeiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('imei') && is_string($this->input('imei'))) {
            $normalized = ImeiValidator::normalizeDigits($this->input('imei'));
            if (strlen($normalized) === 15) {
                $this->merge(['imei' => $normalized]);
            }
        }

        if ($this->input('date_in') === '') {
            $this->merge(['date_in' => null]);
        }

        if ($this->input('selling_price') === '' || $this->input('selling_price') === null) {
            $this->merge(['selling_price' => null]);
        }

        foreach (ImeiOptionalStringFields::KEYS as $key) {
            $value = $this->input($key);
            if ($value === null || $value === '') {
                $this->merge([$key => '']);
            }
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'date_in' => ['nullable', 'date'],
            'stock_take_date' => ['nullable', 'string', 'max:255'],
            'make' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'sn' => ['nullable', 'string', 'max:255'],
            'imei' => ['required', 'string', 'size:15', new ValidImei, new UniqueNormalizedImei],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:65535'],
            'phonenumber' => ['nullable', 'string', 'max:255'],
            'ref' => ['nullable', 'string', 'max:255'],
            'staff' => ['nullable', 'string', 'max:255'],
            'item_code' => ['nullable', 'string', 'max:255'],
            'ourON' => ['nullable', 'string', 'max:255'],
            'salesON' => ['nullable', 'string', 'max:255'],
            'cost_excl' => ['nullable', 'string', 'max:255'],
            'selling_price' => ['nullable', 'integer'],
        ];
    }
}
