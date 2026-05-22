<?php

namespace App\Http\Requests;

use App\Support\BrowseListLimit;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDefaultSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'browse_list_limit' => [
                'required',
                'integer',
                'min:'.BrowseListLimit::MIN_LIMIT,
                'max:'.BrowseListLimit::MAX_LIMIT,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'browse_list_limit.required' => 'Enter the maximum number of records for blank browse lists.',
            'browse_list_limit.min' => 'The limit must be at least '.BrowseListLimit::MIN_LIMIT.'.',
            'browse_list_limit.max' => 'The limit cannot exceed '.BrowseListLimit::MAX_LIMIT.'.',
        ];
    }
}
