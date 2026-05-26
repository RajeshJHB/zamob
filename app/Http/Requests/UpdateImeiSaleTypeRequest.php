<?php

namespace App\Http\Requests;

use App\Models\ImeiSaleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImeiSaleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Unique>>
     */
    public function rules(): array
    {
        /** @var ImeiSaleType $imeiSaleType */
        $imeiSaleType = $this->route('imeiSaleType');

        return [
            'sale_type' => [
                'required',
                'string',
                'max:255',
                Rule::unique('imei_sale_types', 'sale_type')->ignore($imeiSaleType->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sale_type.unique' => 'A sale type with this name already exists.',
        ];
    }
}
