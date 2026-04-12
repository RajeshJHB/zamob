<?php

namespace App\Http\Requests;

use App\Models\ImeiMake;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImeiMakeRequest extends FormRequest
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
        /** @var ImeiMake $imeiMake */
        $imeiMake = $this->route('imeiMake');

        return [
            'make' => [
                'required',
                'string',
                'max:65535',
                Rule::unique('imei_make', 'make')->ignore($imeiMake->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'make.unique' => 'A make with this name already exists.',
        ];
    }
}
