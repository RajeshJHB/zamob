<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVatSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'vat_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vat_percent.required' => 'Enter a VAT percentage.',
            'vat_percent.min' => 'VAT must be at least 0%.',
            'vat_percent.max' => 'VAT cannot exceed 100%.',
        ];
    }
}
