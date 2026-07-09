<?php

namespace App\Http\Requests;

use App\Rules\UniqueNormalizedImei;
use App\Rules\UniqueNormalizedNonStandardImei;
use App\Rules\ValidImei;
use App\Support\ImeiDeletedStatus;
use App\Support\ImeiLinkedServiceNote;
use App\Support\ImeiNewRecordDefaults;
use App\Support\ImeiOptionalStringFields;
use App\Support\ImeiReferenceText;
use App\Support\ImeiValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreImeiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $withoutDateIn = $this->except(['date_in']);
        $this->replace($withoutDateIn);

        if ($this->has('imei') && is_string($this->input('imei'))) {
            if ($this->boolean('imei_non_standard')) {
                $this->merge(['imei' => ImeiValidator::normalizeNonStandard($this->input('imei'))]);
            } else {
                $normalized = ImeiValidator::normalizeDigits($this->input('imei'));
                if (strlen($normalized) === 15) {
                    $this->merge(['imei' => $normalized]);
                }
            }
        }

        if ($this->input('selling_price') === '' || $this->input('selling_price') === null) {
            $this->merge(['selling_price' => null]);
        }

        if (! filled($this->input('cash_stock_type'))) {
            $this->merge(['cash_stock_type' => ImeiNewRecordDefaults::SALE_TYPE]);
        }

        foreach (ImeiOptionalStringFields::KEYS as $key) {
            $value = $this->input($key);
            if ($value === null || $value === '') {
                $this->merge([$key => '']);
            }
        }

        if ($this->has('make') && is_string($this->input('make'))) {
            $this->merge(['make' => ImeiReferenceText::normalize($this->input('make'))]);
        }

        if ($this->has('model') && is_string($this->input('model'))) {
            $this->merge(['model' => ImeiReferenceText::normalize($this->input('model'))]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $base = [
            'imei_non_standard' => ['required', 'in:0,1'],
            'make' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === '' || $value === null) {
                        return;
                    }

                    if (! ImeiReferenceText::makeExists((string) $value)) {
                        $fail('The selected make is invalid.');
                    }
                },
            ],
            'model' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === '' || $value === null) {
                        return;
                    }
                    $make = (string) $this->input('make', '');
                    if ($make === '') {
                        $fail('Choose a make before selecting a model.');

                        return;
                    }
                    if (! ImeiReferenceText::modelExistsForMake($make, (string) $value)) {
                        $fail('The selected model is not valid for this make.');
                    }
                },
            ],
            'sn' => ['nullable', 'string', 'max:255'],
            'location' => [
                'nullable',
                'string',
                'max:255',
                Rule::when(
                    fn (): bool => filled($this->input('location')),
                    ['exists:imei_locations,location'],
                ),
            ],
            'cash_stock_type' => [
                'nullable',
                'string',
                'max:255',
                Rule::when(
                    fn (): bool => filled($this->input('cash_stock_type')),
                    ['exists:imei_sale_types,sale_type'],
                ),
            ],
            'type' => [
                'nullable',
                'string',
                'max:255',
                Rule::when(
                    fn (): bool => filled($this->input('type')),
                    ['exists:imei_types,type'],
                ),
            ],
            'status' => [
                'nullable',
                'string',
                'max:255',
                Rule::when(
                    fn (): bool => filled($this->input('status')),
                    ['exists:imei_statuses,status'],
                ),
            ],
            'notes' => ['nullable', 'string', 'max:'.\App\Support\ImeiTextLimits::CUSTOMER_DETAILS_MAX],
            'phonenumber' => ['nullable', 'string', 'max:255'],
            'ref' => ['nullable', 'string', 'max:'.\App\Support\ImeiTextLimits::DEAL_DETAILS_MAX],
            'item_code' => ['nullable', 'string', 'max:255'],
            'ourON' => ['nullable', 'string', 'max:255'],
            'salesON' => ['nullable', 'string', 'max:255'],
            'cost_excl' => ['nullable', 'string', 'max:255'],
            'selling_price' => ['nullable', 'integer'],
        ];

        if ($this->boolean('imei_non_standard')) {
            $base['imei'] = [
                'required',
                'string',
                'min:1',
                'max:'.ImeiValidator::MAX_NON_STANDARD_IMEI_LENGTH,
                new UniqueNormalizedNonStandardImei,
            ];

            return array_merge($base, ImeiLinkedServiceNote::validationRules());
        }

        $base['imei'] = ['required', 'string', 'size:15', new ValidImei, new UniqueNormalizedImei];

        return array_merge($base, ImeiLinkedServiceNote::validationRules());
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((string) $this->input('status', '') === ImeiDeletedStatus::VALUE
                && ! ImeiDeletedStatus::userCanViewDeleted($this->user())) {
                $validator->errors()->add('status', 'You cannot set this status.');
            }
        });
    }
}
