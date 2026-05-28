<?php

namespace App\Http\Requests;

use App\Support\ImeiBulkEdit;
use App\Support\ImeiTextLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkEditImeiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canBulkEditImei() === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'field_filter_1' => ['nullable', 'string', 'max:255'],
            'field_value_1' => ['nullable', 'string', 'max:255'],
            'field_filter_2' => ['nullable', 'string', 'max:255'],
            'field_value_2' => ['nullable', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'max:255'],
            'columns' => ['nullable'],
            'date_scope' => ['nullable', 'string', 'max:255'],
            'date_column' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'string', 'max:255'],
            'end_date' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'search2' => ['nullable', 'string', 'max:255'],
            'sort1_column' => ['nullable', 'string', 'max:255'],
            'sort1_dir' => ['nullable', 'string', 'max:255'],
            'sort2_column' => ['nullable', 'string', 'max:255'],
            'sort2_dir' => ['nullable', 'string', 'max:255'],
            'profile_id' => ['nullable', 'integer'],
        ];

        $rules['search_'.ImeiBulkEdit::FIELD_SALE_TYPE] = ['nullable', 'string', 'max:255', 'exists:imei_sale_types,sale_type'];
        $rules['search_'.ImeiBulkEdit::FIELD_STATUS] = ['nullable', 'string', 'max:255', 'exists:imei_statuses,status'];
        $rules['search_'.ImeiBulkEdit::FIELD_DEAL_DETAILS] = ['nullable', 'string', 'max:'.ImeiTextLimits::DEAL_DETAILS_MAX];
        $rules['search_'.ImeiBulkEdit::FIELD_CUSTOMER_DETAILS] = ['nullable', 'string', 'max:'.ImeiTextLimits::CUSTOMER_DETAILS_MAX];

        $rules['replace_'.ImeiBulkEdit::FIELD_SALE_TYPE] = ['nullable', 'string', 'max:255', 'exists:imei_sale_types,sale_type'];
        $rules['replace_'.ImeiBulkEdit::FIELD_STATUS] = ['nullable', 'string', 'max:255', 'exists:imei_statuses,status'];
        $rules['replace_'.ImeiBulkEdit::FIELD_DEAL_DETAILS] = ['nullable', 'string', 'max:'.ImeiTextLimits::DEAL_DETAILS_MAX];
        $rules['replace_'.ImeiBulkEdit::FIELD_CUSTOMER_DETAILS] = ['nullable', 'string', 'max:'.ImeiTextLimits::CUSTOMER_DETAILS_MAX];

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! ImeiBulkEdit::hasAnySearchValue($this->all())) {
                $validator->errors()->add('search_sale_type', 'Provide at least one search value to match records.');
            }

            if (! ImeiBulkEdit::hasAnyReplaceValue($this->all())) {
                $validator->errors()->add('replace_sale_type', 'Provide at least one replace value to apply.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function searchCriteria(): array
    {
        return $this->only(array_map(
            fn (string $fieldKey): string => 'search_'.$fieldKey,
            ImeiBulkEdit::FIELD_KEYS,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function replaceValues(): array
    {
        return $this->only(array_map(
            fn (string $fieldKey): string => 'replace_'.$fieldKey,
            ImeiBulkEdit::FIELD_KEYS,
        ));
    }
}
