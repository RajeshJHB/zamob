<?php

namespace App\Http\Requests;

use App\Models\Imei;
use App\Models\ImeiModel;
use App\Support\ImeiDeletedStatus;
use App\Support\ImeiOptionalStringFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateImeiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
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
            'make' => [
                'nullable',
                'string',
                'max:255',
                Rule::when(
                    function (): bool {
                        if ((string) $this->input('make', '') === '') {
                            return false;
                        }
                        $imei = $this->route('imei');
                        if ($imei instanceof Imei && $imei->make === $this->input('make')) {
                            return false;
                        }

                        return true;
                    },
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
                    $imei = $this->route('imei');
                    if ($imei instanceof Imei && $imei->make === $make && $imei->model === (string) $value) {
                        return;
                    }
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
                    function (): bool {
                        $value = (string) $this->input('location', '');
                        if ($value === '') {
                            return false;
                        }
                        $imei = $this->route('imei');
                        if ($imei instanceof Imei && $imei->location === $value) {
                            return false;
                        }

                        return true;
                    },
                    ['exists:imei_locations,location'],
                ),
            ],
            'type' => [
                'nullable',
                'string',
                'max:255',
                Rule::when(
                    function (): bool {
                        $value = (string) $this->input('type', '');
                        if ($value === '') {
                            return false;
                        }
                        $imei = $this->route('imei');
                        if ($imei instanceof Imei && $imei->type === $value) {
                            return false;
                        }

                        return true;
                    },
                    ['exists:imei_types,type'],
                ),
            ],
            'status' => [
                'nullable',
                'string',
                'max:255',
                Rule::when(
                    function (): bool {
                        $value = (string) $this->input('status', '');
                        if ($value === '' || $value === ImeiDeletedStatus::VALUE) {
                            return false;
                        }
                        $imei = $this->route('imei');
                        if ($imei instanceof Imei && $imei->status === $value) {
                            return false;
                        }

                        return true;
                    },
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
