<?php

namespace App\Http\Requests;

use App\Support\ImeiFieldFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkChangeImeiStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canDeleteImeiReferenceData() === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status_to' => ['required', 'string', 'max:255', 'exists:imei_statuses,status'],
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status_to.required' => 'Choose the new status.',
            'status_to.exists' => 'The selected status is not valid.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (ImeiFieldFilter::statusFilterValue($this) === null) {
                $validator->errors()->add('field_filter', 'A status field filter is required for bulk status change.');
            }

            $fromStatus = ImeiFieldFilter::statusFilterValue($this);
            $toStatus = (string) $this->input('status_to', '');

            if ($fromStatus !== null && $toStatus !== '' && $fromStatus === $toStatus) {
                $validator->errors()->add('status_to', 'The new status must be different from the filtered status.');
            }
        });
    }
}
