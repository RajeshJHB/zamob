<?php

namespace App\Http\Requests;

use App\Models\ImeiModel;
use App\Rules\UniqueNormalizedImei;
use App\Rules\UniqueNormalizedNonStandardImei;
use App\Rules\ValidImei;
use App\Support\ImeiDeletedStatus;
use App\Support\ImeiOptionalStringFields;
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
        $base = [
            'imei_non_standard' => ['required', 'in:0,1'],
            'make' => [
                'nullable',
                'string',
                'max:255',
                Rule::when(
                    fn (): bool => filled($this->input('make')),
                    ['exists:imei_make,make'],
                ),
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
                    if (! ImeiModel::query()->where('make', $make)->where('model', (string) $value)->exists()) {
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
            'stock_take_date' => [
                'nullable',
                'string',
                'max:255',
                Rule::when(
                    fn (): bool => filled($this->input('stock_take_date')),
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

            return $base;
        }

        $base['imei'] = ['required', 'string', 'size:15', new ValidImei, new UniqueNormalizedImei];

        return $base;
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
