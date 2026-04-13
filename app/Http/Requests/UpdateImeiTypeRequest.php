<?php

namespace App\Http\Requests;

use App\Models\ImeiType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImeiTypeRequest extends FormRequest
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
        /** @var ImeiType $imeiType */
        $imeiType = $this->route('imeiType');

        return [
            'type' => [
                'required',
                'string',
                'max:65535',
                Rule::unique('imei_types', 'type')->ignore($imeiType->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.unique' => 'A type with this name already exists.',
        ];
    }
}
