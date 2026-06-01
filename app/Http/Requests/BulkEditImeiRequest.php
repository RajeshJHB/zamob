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
            'field_filter_3' => ['nullable', 'string', 'max:255'],
            'field_value_3' => ['nullable', 'string', 'max:255'],
            'field_not_1' => ['nullable', 'boolean'],
            'field_not_2' => ['nullable', 'boolean'],
            'field_not_3' => ['nullable', 'boolean'],
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
        $rules['search_'.ImeiBulkEdit::FIELD_TYPE] = ['nullable', 'string', 'max:255', 'exists:imei_types,type'];
        $rules['search_'.ImeiBulkEdit::FIELD_DEAL_DETAILS] = ['nullable', 'string', 'max:'.ImeiTextLimits::DEAL_DETAILS_MAX];
        $rules['search_'.ImeiBulkEdit::FIELD_CUSTOMER_DETAILS] = ['nullable', 'string', 'max:'.ImeiTextLimits::CUSTOMER_DETAILS_MAX];

        $rules['replace_'.ImeiBulkEdit::FIELD_SALE_TYPE] = ['nullable', 'string', 'max:255', 'exists:imei_sale_types,sale_type'];
        $rules['replace_'.ImeiBulkEdit::FIELD_STATUS] = ['nullable', 'string', 'max:255', 'exists:imei_statuses,status'];
        $rules['replace_'.ImeiBulkEdit::FIELD_TYPE] = ['nullable', 'string', 'max:255', 'exists:imei_types,type'];
        $rules['replace_'.ImeiBulkEdit::FIELD_DEAL_DETAILS] = ['nullable', 'string', 'max:'.ImeiTextLimits::DEAL_DETAILS_MAX];
        $rules['replace_'.ImeiBulkEdit::FIELD_CUSTOMER_DETAILS] = ['nullable', 'string', 'max:'.ImeiTextLimits::CUSTOMER_DETAILS_MAX];

        foreach (ImeiBulkEdit::TEXT_PARTIAL_EDIT_FIELD_KEYS as $fieldKey) {
            $rules[ImeiBulkEdit::removeSearchTextRequestKey($fieldKey)] = ['nullable', 'boolean'];
            $rules[ImeiBulkEdit::replaceSearchTextRequestKey($fieldKey)] = ['nullable', 'boolean'];
        }

        foreach (ImeiBulkEdit::EXACT_MATCH_SEARCH_FIELD_KEYS as $fieldKey) {
            $rules[ImeiBulkEdit::excludeSearchRequestKey($fieldKey)] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! ImeiBulkEdit::hasAnySearchValue($this->all())) {
                $validator->errors()->add('search_sale_type', 'Provide at least one search value to match records.');
            }

            if (! ImeiBulkEdit::hasAnyReplaceAction($this->all())) {
                $validator->errors()->add('replace_sale_type', 'Provide at least one replace value or remove-search option to apply.');
            }

            foreach (ImeiBulkEdit::EXACT_MATCH_SEARCH_FIELD_KEYS as $fieldKey) {
                $excludeKey = ImeiBulkEdit::excludeSearchRequestKey($fieldKey);
                $searchKey = 'search_'.$fieldKey;
                $label = ImeiBulkEdit::labels()[$fieldKey];

                if ($this->boolean($excludeKey) && trim((string) $this->input($searchKey, '')) === '') {
                    $validator->errors()->add($searchKey, "Choose a {$label} when using exclude (not equal).");
                }
            }

            foreach (ImeiBulkEdit::TEXT_PARTIAL_EDIT_FIELD_KEYS as $fieldKey) {
                $removeKey = ImeiBulkEdit::removeSearchTextRequestKey($fieldKey);
                $replaceSearchKey = ImeiBulkEdit::replaceSearchTextRequestKey($fieldKey);
                $searchKey = 'search_'.$fieldKey;
                $replaceKey = 'replace_'.$fieldKey;
                $label = ImeiBulkEdit::labels()[$fieldKey];

                if ($this->boolean($removeKey) && $this->boolean($replaceSearchKey)) {
                    $validator->errors()->add($replaceKey, "Choose either remove or replace-search-text for {$label}, not both.");
                }

                if ($this->boolean($removeKey)) {
                    if (trim((string) $this->input($searchKey, '')) === '') {
                        $validator->errors()->add($searchKey, "Provide a search value for {$label} when removing search text.");
                    }

                    if (trim((string) $this->input($replaceKey, '')) !== '') {
                        $validator->errors()->add($replaceKey, "Clear the replace value for {$label} when using remove search text.");
                    }
                }

                if ($this->boolean($replaceSearchKey)) {
                    if (trim((string) $this->input($searchKey, '')) === '') {
                        $validator->errors()->add($searchKey, "Provide a search value for {$label} when replacing search text.");
                    }

                    if (trim((string) $this->input($replaceKey, '')) === '') {
                        $validator->errors()->add($replaceKey, "Provide the replacement text for {$label} when using replace search text.");
                    }
                }
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function searchCriteria(): array
    {
        $keys = array_map(
            fn (string $fieldKey): string => 'search_'.$fieldKey,
            ImeiBulkEdit::FIELD_KEYS,
        );

        foreach (ImeiBulkEdit::EXACT_MATCH_SEARCH_FIELD_KEYS as $fieldKey) {
            $keys[] = ImeiBulkEdit::excludeSearchRequestKey($fieldKey);
        }

        return $this->only($keys);
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
